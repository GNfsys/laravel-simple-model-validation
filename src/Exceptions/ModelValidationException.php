<?php

declare(strict_types=1);

namespace GNfsys\ModelValidation\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;

/**
 * This is necessary for the exception to be logged by Laravel,
 * because `ValidationException` is meant to be transformed to a `Response`
 */
final class ModelValidationException extends Exception
{
    public function __construct(private readonly ValidationException|string $exception)
    {
        if (is_string($this->exception)) {
            parent::__construct($this->exception);

            return;
        }

        parent::__construct(
            $this->exception->getMessage(),
            $this->exception->getCode(),
            $this->exception
        );
    }

    public function getValidationException(): ?ValidationException
    {
        return $this->exception instanceof ValidationException ? $this->exception : null;
    }
}
