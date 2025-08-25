<?php

namespace Mllexx\IFS\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Mllexx\IFS\DTO\ApiResponse;
use Mllexx\IFS\Exceptions\IFSException;
use Mllexx\IFS\Http\ResponseFactory;

class IFSClient
{
    protected GuzzleClient $client;
    protected array $config;
    protected ResponseFactory $responseFactory;
    protected ?string $accessToken = null;
    protected ?int $tokenExpiresAt = null;
    protected ?string $tokenEndpoint = null;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'base_uri' => config('ifs.base_uri'),
            'timeout' => 60,
            'client_id' => config('ifs.client_id'),
            'client_secret' => config('ifs.client_secret'),
            'token_endpoint' => config('ifs.token_endpoint'),
            'token_ttl' => 3600, // 1 hour default
            'token_cache_key' => 'ifs_oauth_token',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ], $config);
        $this->tokenEndpoint = $this->config['token_endpoint']; 

        $this->client = new GuzzleClient([
            'base_uri' => rtrim($this->config['base_uri'], '/') . '/',
            'timeout' => $this->config['timeout'],
            'http_errors' => true,
        ]);

        $this->responseFactory = new ResponseFactory();
    }

    /**
     * Make a GET request to the IFS API
     *
     * @param string $endpoint
     * @param array $query
     * @return ApiResponse
     * @throws IFSException
     */
    public function get(string $endpoint, array $query = []): ApiResponse
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    /**
     * Make a POST request to the IFS API
     *
     * @param string $endpoint
     * @param array $data
     * @return ApiResponse
     * @throws IFSException
     */
    public function post(string $endpoint, array $data = []): ApiResponse
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Make a PUT request to the IFS API
     *
     * @param string $endpoint
     * @param array $data
     * @return ApiResponse
     * @throws IFSException
     */
    public function put(string $endpoint, array $data = []): ApiResponse
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * Make a PATCH request to the IFS API
     *
     * @param string $endpoint
     * @param array $data
     * @return ApiResponse
     * @throws IFSException
     */
    public function patch(string $endpoint, array $data = []): ApiResponse
    {
        return $this->request('PATCH', $endpoint, ['json' => $data]);
    }

    /**
     * Make a DELETE request to the IFS API
     *
     * @param string $endpoint
     * @return ApiResponse
     * @throws IFSException
     */
    public function delete(string $endpoint): ApiResponse
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Make a request to the IFS API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return ApiResponse
     * @throws IFSException
     */
    protected function request(string $method, string $endpoint, array $options = []): ApiResponse
    {
        try {
            // Create a request object first
            $request = new \GuzzleHttp\Psr7\Request(
                $method,
                ltrim($endpoint, '/'),
                $this->getDefaultOptions()['headers'],
                isset($options['json']) ? json_encode($options['json']) : null
            );

            $response = $this->client->sendAsync($request)->wait();
            $statusCode = $response->getStatusCode();
            // get streamed data because its an async request
            $stream = $response->getBody();
            $responseData = json_decode((string) $stream->getContents(), true) ?? [];
            $headers = $response->getHeaders();

            // Create and return a standardized API response
            $apiResponse = $this->responseFactory->createApiResponse(
                $responseData,
                $headers,
                $statusCode
            );

            // If the request was not successful, throw an exception with the API response
            if (! $apiResponse->isSuccessful()) {
                throw new IFSException(
                    $apiResponse->getMessage() ?? 'API request failed',
                    $statusCode,
                    null,
                    ['response' => $responseData]
                );
            }

            return $apiResponse;
        } catch (GuzzleException $e) {
            throw new IFSException("HTTP Request failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get default request options with authentication
     *
     * @return array
     * @throws IFSException
     */
    protected function getDefaultOptions(): array
    {
        $headers = $this->config['headers'];
        
        // Add Authorization header if token exists
        if ($token = $this->getAccessToken()) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return [
            'headers' => $headers,
            'timeout' => $this->config['timeout'] ?? 60,
        ];
    }

    /**
     * Get access token using client credentials
     *
     * @return string
     * @throws IFSException
     */
    protected function getAccessToken(): string
    {
        // Return cached token if valid
        if ($this->accessToken && $this->tokenExpiresAt > time() + 60) {
            return $this->accessToken;
        }

        // Try to get from cache if available
        if (function_exists('cache') && $cachedToken = cache($this->config['token_cache_key'])) {
            $this->accessToken = $cachedToken['access_token'];
            $this->tokenExpiresAt = $cachedToken['expires_at'];
            
            if ($this->tokenExpiresAt > time() + 60) {
                return $this->accessToken;
            }
        }

        // Request new token
        try {
            $response = $this->client->post($this->tokenEndpoint, [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                ],
                'timeout' => 50,
                'http_errors' => true,
            ]);

            $data = json_decode((string) $response->getBody(), true);
            
            if (!isset($data['access_token'])) {
                throw new IFSException('Failed to obtain access token');
            }

            $this->accessToken = $data['access_token'];
            $this->tokenExpiresAt = time() + ($data['expires_in'] ?? $this->config['token_ttl']);

            // Cache the token
            if (function_exists('cache')) {
                cache()->put(
                    $this->config['token_cache_key'],
                    [
                        'access_token' => $this->accessToken,
                        'expires_at' => $this->tokenExpiresAt,
                    ],
                    ($this->tokenExpiresAt - time()) / 60 // Cache for remaining minutes
                );
            }

            return $this->accessToken;
        } catch (GuzzleException $e) {
            throw new IFSException('OAuth token request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get the response factory instance
     *
     * @return ResponseFactory
     */
    public function getResponseFactory(): ResponseFactory
    {
        return $this->responseFactory;
    }

    /**
     * Set a custom response factory
     *
     * @param ResponseFactory $responseFactory
     * @return void
     */
    public function setResponseFactory(ResponseFactory $responseFactory): void
    {
        $this->responseFactory = $responseFactory;
    }
}
