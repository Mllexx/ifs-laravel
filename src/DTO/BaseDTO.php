<?php

namespace Mllexx\IFS\DTO;

use ArrayAccess;
use JsonSerializable;

abstract class BaseDTO implements ArrayAccess, JsonSerializable
{
    /**
     * The model's attributes
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * The attributes that should be cast
     *
     * @var array
     */
    protected array $casts = [];

    /**
     * The attributes that are mass assignable
     *
     * @var array
     */
    protected array $fillable = [];

    /**
     * Create a new DTO instance
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Fill the DTO with an array of attributes
     *
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Set a given attribute on the DTO
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $key, $value): self
    {
        if ($this->isFillable($key)) {
            $this->attributes[$key] = $this->castAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Get an attribute from the DTO
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute(string $key, $default = null)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return $default;
    }

    /**
     * Determine if the given attribute exists
     *
     * @param string $key
     * @return bool
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Determine if the given key is fillable
     *
     * @param string $key
     * @return bool
     */
    protected function isFillable(string $key): bool
    {
        return in_array($key, $this->fillable) || empty($this->fillable);
    }

    /**
     * Cast an attribute to a native PHP type
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function castAttribute(string $key, $value)
    {
        if (is_null($value) || ! isset($this->casts[$key])) {
            return $value;
        }

        $type = $this->casts[$key];

        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'array':
                return is_string($value) ? json_decode($value, true) : (array) $value;
            case 'json':
                return is_string($value) ? json_decode($value, false) : $value;
            case 'datetime':
                return is_numeric($value) ? (new \DateTime())->setTimestamp($value) : new \DateTime($value);
            default:
                return $value;
        }
    }

    /**
     * Get all of the current attributes on the DTO
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Convert the DTO to JSON
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the DTO into something JSON serializable
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Determine if the given offset exists
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->hasAttribute($offset);
    }

    /**
     * Get the value for a given offset
     *
     * @param string $offset
     * @return mixed
     */
    #\[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset
     *
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Unset the value for a given offset
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Dynamically retrieve attributes on the DTO
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the DTO
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Check if an attribute exists on the DTO
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return $this->hasAttribute($key);
    }

    /**
     * Unset an attribute on the DTO
     *
     * @param string $key
     * @return void
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * Convert the DTO to its string representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson(JSON_PRETTY_PRINT);
    }
}
