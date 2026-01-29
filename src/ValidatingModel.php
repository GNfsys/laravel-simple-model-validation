<?php

declare(strict_types=1);

namespace GNfsys\ModelValidation;

use GNfsys\ModelValidation\Exceptions\ModelValidationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

abstract class ValidatingModel extends Model
{
    protected bool $validateOnSave = true;

    /**
     * Get the validation rules that apply to the model.
     *
     * @return array<non-empty-string, ValidationRule|array<mixed>|string>
     *
     * @phpstan-return array<model-property<static>, ValidationRule|array<mixed>|string>
     */
    abstract protected function rules(): array;

    /**
     * Override this if you need extra checks after the model was validated.
     * You can throw `ModelValidationException` to simulate a validation error.
     */
    protected function afterValidation(): void
    {
        //
    }

    private function make(): Validator
    {
        $attributes = $this->attributesToArray();

        $rules = $this->exists
            ? array_intersect_key($this->rules(), $attributes)
            : $this->rules();

        return ValidatorFacade::make($attributes, $rules);
    }

    /**
     * Run the validator's rules against the model.
     *
     * @return array<mixed>
     *
     * @throws ModelValidationException
     */
    final public function validate(): array
    {
        try {
            return $this->make()->validate();
        } catch (ValidationException $exception) {
            throw new ModelValidationException($exception);
        }
    }

    /** @throws ModelValidationException */
    final public function check(): void
    {
        $validator = $this->make();

        if ($validator->fails()) {
            $exceptionClass = $validator->getException();

            throw new ModelValidationException(new $exceptionClass($validator));
        }
    }

    final public function isValid(): bool
    {
        return $this->make()->passes();
    }

    final public function isInvalid(): bool
    {
        return ! $this->isValid();
    }

    protected static function booted(): void
    {
        static::saving(function (ValidatingModel $model): void {
            if ($model->validateOnSave && $model->isDirty()) {
                $model->check();

                $model->afterValidation();
            }
        });
    }

    /** @param array<non-empty-string, mixed> $options */
    final public function saveWithoutValidation(array $options = []): bool
    {
        $originalValue = $this->validateOnSave;

        $this->validateOnSave = false;

        try {
            return $this->save($options);
        } finally {
            $this->validateOnSave = $originalValue;
        }
    }
}
