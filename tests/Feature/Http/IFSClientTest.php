<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mllexx\IFS\DTO\ApiResponse;
use Mllexx\IFS\Exceptions\IFSException;
use Mllexx\IFS\Http\IFSClient;
use Mllexx\IFS\Http\ResponseFactory;

beforeEach(function () {
    // Mock handler for Guzzle
    $this->mockHandler = new MockHandler();
    
    // Create a handler stack with our mock handler
    $handlerStack = HandlerStack::create($this->mockHandler);
    
    // Create a Guzzle client with the mock handler
    $this->client = new IFSClient([
        'base_uri' => 'https://ifs-cld-cfg-mdlw.bulkstream.com/main/ifsapplications/projection/v1',
        'client_id' => 'IFS_connect',
        'client_secret' => 'GptpzMVJfdzBpXxDecTW',
        'token_endpoint' => 'https://ifs-cld-cfg-mdlw.bulkstream.com/auth/realms/blcfg/protocol/openid-connect/token',
        'timeout' => 5,
        'handler' => $handlerStack,
    ]);
});

test('it can be instantiated', function () {
    expect($this->client)->toBeInstanceOf(IFSClient::class);
});

test('it gets access token using client credentials', function () {
    // Mock token response
    $this->mockHandler->append(new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ])
    ));

    // Mock API response that will use the token
    $this->mockHandler->append(new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode(['data' => 'test'])
    ));

    // Make a request that will trigger token fetch
    $response = $this->client->get('/test');
    
    expect($response)->toBeInstanceOf(ApiResponse::class);
    expect($response->isSuccessful())->toBeTrue();
});

test('it uses cached token when available', function () {
    // Set up a cached token
    $cachedToken = [
        'access_token' => 'cached-token',
        'expires_at' => time() + 3600,
    ];
    
    // Mock cache get
    \Illuminate\Support\Facades\Cache::shouldReceive('get')
        ->once()
        ->with('ifs_oauth_token')
        ->andReturn($cachedToken);
    
    // Mock API response (should use cached token)
    $this->mockHandler->append(new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode(['data' => 'test'])
    ));
    
    $response = $this->client->get('/test');
    
    expect($response)->toBeInstanceOf(ApiResponse::class);
    expect($response->isSuccessful())->toBeTrue();
});

test('it handles token request failure', function () {
    // Mock failed token response
    $this->mockHandler->append(new Response(
        400,
        ['Content-Type' => 'application/json'],
        json_encode(['error' => 'invalid_client'])
    ));
    
    // This should throw an exception
    $this->client->get('/test');
})->throws(IFSException::class, 'Failed to obtain access token');

test('it makes GET requests', function () {
    // Mock token response
    $this->mockHandler->append(new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode([
            'access_token' => 'test-token',
            'expires_in' => 3600,
        ])
    )); // Added missing closing parenthesis here
    
    // Mock API response
    $this->mockHandler->append(new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode(['data' => 'test'])
    ));
    
    $response = $this->client->get('/test', ['param' => 'value']);
    
    expect($response)->toBeInstanceOf(ApiResponse::class);
    expect($response->isSuccessful())->toBeTrue();
    expect($response->getData())->toBe(['data' => 'test']);
});

test('it makes POST requests', function () {
    // Mock token response
    $this->mockHandler->append(new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode([
            'access_token' => 'test-token',
            'expires_in' => 3600,
        ])
    ));
    
    // Mock API response
    $this->mockHandler->append(new Response(
        201,
        ['Content-Type' => 'application/json'],
        json_encode(['id' => 123, 'name' => 'Test'])
    ));
    
    $response = $this->client->post('/items', ['name' => 'Test']);
    
    expect($response)->toBeInstanceOf(ApiResponse::class);
    expect($response->isSuccessful())->toBeTrue();
    expect($response->getData())->toHaveKey('id', 123);
});

test('it handles API errors', function () {
    // Mock token response
    $this->mockHandler->append(new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode([
            'access_token' => 'test-token',
            'expires_in' => 3600,
        ])
    ));
    
    // Mock error response
    $this->mockHandler->append(new Response(
        404,
        ['Content-Type' => 'application/json'],
        json_encode([
            'error' => 'Not Found',
            'message' => 'The requested resource was not found',
        ])
    ));
    
    $response = $this->client->get('/nonexistent');
    
    expect($response->isSuccessful())->toBeFalse();
    expect($response->getStatusCode())->toBe(404);
});
