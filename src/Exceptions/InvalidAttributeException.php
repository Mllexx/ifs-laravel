<?php

namespace Mllexx\IFS\Exceptions;

/**
 * Exception thrown when an invalid attribute is accessed or modified
 */
class InvalidAttributeException extends IFSException
{
    /**
     * The name of the invalid attribute
     *
     * @var string
     */
    protected string $attribute;

    /**
     * Create a new exception instance
     *
     * @param string $attribute
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $attribute = "",
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->attribute = $attribute;
        $message = $message ?: "The attribute [{$attribute}] is invalid or not fillable.";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the name of the invalid attribute
     *
     * @return string
     */
    public function getAttribute(): string
    {
        return $this->attribute;
    }
}
