<?php

namespace Mllexx\IFS\DTO;

/**
 * Class ApiResponse
 *
 * Represents a standardized API response from IFS
 */
class ApiResponse
{
    /**
     * @var bool Indicates if the request was successful
     */
    protected bool $success;

    /**
     * @var mixed The response data
     */
    protected $data;

    /**
     * @var string|null Error message if the request failed
     */
    protected ?string $message;

    /**
     * @var array Additional metadata
     */
    protected array $meta;

    /**
     * @var array Response headers
     */
    protected array $headers;

    /**
     * @var int HTTP status code
     */
    protected int $statusCode;

    /**
     * Create a new API response instance
     *
     * @param bool $success
     * @param mixed $data
     * @param string|null $message
     * @param array $meta
     * @param array $headers
     * @param int $statusCode
     */
    public function __construct(
        bool $success,
        $data = null,
        ?string $message = null,
        array $meta = [],
        array $headers = [],
        int $statusCode = 200
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->message = $message;
        $this->meta = $meta;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
    }

    /**
     * Create a successful response
     *
     * @param mixed $data
     * @param string|null $message
     * @param array $meta
     * @param array $headers
     * @param int $statusCode
     * @return static
     */
    public static function success(
        $data = null,
        ?string $message = null,
        array $meta = [],
        array $headers = [],
        int $statusCode = 200
    ): self {
        return new static(true, $data, $message, $meta, $headers, $statusCode);
    }

    /**
     * Create an error response
     *
     * @param string $message
     * @param mixed $data
     * @param array $meta
     * @param array $headers
     * @param int $statusCode
     * @return static
     */
    public static function error(
        string $message,
        $data = null,
        array $meta = [],
        array $headers = [],
        int $statusCode = 400
    ): self {
        return new static(false, $data, $message, $meta, $headers, $statusCode);
    }

    /**
     * Check if the request was successful
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Get the response data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the error message
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get the response metadata
     *
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * Get a specific metadata value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetaValue(string $key, $default = null)
    {
        return $this->meta[$key] ?? $default;
    }

    /**
     * Get the response headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a specific header value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getHeader(string $key, $default = null)
    {
        $key = strtolower($key);
        $headers = array_change_key_case($this->headers, CASE_LOWER);

        return $headers[$key] ?? $default;
    }

    /**
     * Get the HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Convert the response to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'message' => $this->message,
            'meta' => $this->meta,
            'status_code' => $this->statusCode,
        ];
    }

    /**
     * Convert the response to JSON
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Convert the response to a string (JSON)
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson(JSON_PRETTY_PRINT);
    }
}
