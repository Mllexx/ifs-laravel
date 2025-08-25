<?php

namespace Mllexx\IFS\Exceptions;

use Exception;
use Throwable;

class IFSException extends Exception
{
    /**
     * Additional exception data
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param array $context
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the exception context
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create a new exception with context
     *
     * @param array $context
     * @return static
     */
    public function withContext(array $context): self
    {
        $new = new static($this->message, $this->code, $this->getPrevious(), $context);
        $new->file = $this->file;
        $new->line = $this->line;

        return $new;
    }
}
