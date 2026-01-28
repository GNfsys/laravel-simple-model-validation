<?php

declare(strict_types=1);

namespace GNfsys\ModelValidation;

use Exception;
use Illuminate\Validation\ValidationException;

/**
 * This is necessary for the exception to be logged by Laravel,
 * because `ValidationException` is meant to be transformed to a `Response`
 */
final class ModelValidationException extends Exception
{
    public function __construct(private readonly ValidationException $exception)
    {
        parent::__construct(
            $this->exception->getMessage(),
            $this->exception->getCode(),
            $this->exception
        );
    }

    public function getValidationException(): ValidationException
    {
        return $this->exception;
    }
}