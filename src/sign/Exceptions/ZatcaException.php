<?php

namespace  ZATCA\sign\Exceptions;

use Exception;
use Throwable;

/**
 * Class ZatcaException
 *
 * Base exception class for ZATCA-related errors.
 */
class ZatcaException extends Exception
{
    protected string $defaultMessage = 'An error occurred';
    protected array $context = [];

    public function __construct(?string $message = null, array $context = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message ?? $this->getDefaultMessage(), $code, $previous);
        $this->context = $context;
    }

    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getDefaultMessage(): string
    {
        return $this->defaultMessage;
    }
}
