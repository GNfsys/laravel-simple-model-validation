<?php

declare(strict_types=1);

namespace GNfsys\ModelValidation;


use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

abstract class ValidatingModel extends Model
{
    protected bool $validateOnSave = true;

    /**
     * Get the validation rules that apply to the model
     *
     * @return array<non-empty-string, ValidationRule|array<mixed>|string>
     *
     * @phpstan-return array<model-property<static>, ValidationRule|array<mixed>|string>
     */
    abstract protected function rules(): array;

    private function make(): Validator
    {
        return ValidatorFacade::make($this->attributesToArray(), $this->rules());
    }

    /**
     * Run the validator's rules against the model.
     *
     * @return array<mixed>
     *
     * @throws ValidationException
     */
    final protected function validate(): array
    {
        return $this->make()->validate();
    }

    /** @throws ValidationException */
    final protected function check(): void
    {
        $validator = $this->make();

        if ($validator->fails()) {
            $exceptionClass = $validator->getException();

            throw new $exceptionClass($validator);
        }
    }

    final protected function isValid(): bool
    {
        return $this->make()->passes();
    }

    final protected function isInvalid(): bool
    {
        return !$this->isValid();
    }

    protected static function booted(): void
    {
        static::saving(function (ValidatingModel $model): void {
            if ($model->validateOnSave && $model->isDirty()) {
                $model->check();

                if (method_exists($model, 'afterValidation')) {
                    $model->afterValidation();
                }
            }
        });
    }

    /** @param array<non-empty-string, mixed> $options */
    final protected function saveWithoutValidation(array $options = []): bool
    {
        $originalValue = $this->validateOnSave;

        $this->validateOnSave = false;

        $saved = $this->save($options);

        $this->validateOnSave = $originalValue;

        return $saved;
    }
}
