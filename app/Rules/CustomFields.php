<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomFields implements ValidationRule
{
    // Validate empty strings too; omission is handled by sometimes in rules().
    public bool $implicit = true;

    public const MAX_FIELDS = 50;

    public const MAX_ID_LENGTH = 100;

    public const MAX_LABEL_LENGTH = 255;

    public const MAX_VALUE_LENGTH = 5000;

    public const MAX_CONTACT_LENGTH = 255;

    public const TYPES = ['text', 'textarea', 'email', 'tel', 'number', 'select', 'checkbox'];

    public static function rules(): array
    {
        return ['custom_fields' => ['bail', 'sometimes', 'nullable', 'array', 'list', 'max:'.self::MAX_FIELDS, new self]];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! is_array($value) || ! array_is_list($value) || count($value) > self::MAX_FIELDS) {
            $fail('El atributo :attribute debe ser una lista de hasta '.self::MAX_FIELDS.' campos.');

            return;
        }

        $ids = [];

        foreach ($value as $index => $field) {
            if (! is_array($field)) {
                $fail("El campo {$attribute}.{$index} debe ser un objeto.");

                continue;
            }

            $validator = Validator::make(['field' => $field], [
                'field' => ['array:id,label,type,value'],
                'field.id' => ['required', 'string', 'max:'.self::MAX_ID_LENGTH],
                'field.label' => ['required', 'string', 'max:'.self::MAX_LABEL_LENGTH],
                'field.type' => ['required', Rule::in(self::TYPES)],
                'field.value' => ['present'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $fail("{$attribute}.{$index}: {$message}");
                }

                continue;
            }

            if (in_array($field['id'], $ids, true)) {
                $fail("El identificador de {$attribute}.{$index} está repetido.");
            }
            $ids[] = $field['id'];

            if (! $this->validValue($field['type'], $field['value'])) {
                $fail("El valor de {$attribute}.{$index} no es válido para el tipo {$field['type']} o supera su longitud máxima.");
            }
        }
    }

    private function validValue(string $type, mixed $value): bool
    {
        if ($type === 'checkbox') {
            return is_bool($value);
        }

        if ($type === 'number') {
            return (is_int($value) || is_float($value)) && is_finite((float) $value);
        }

        if (! is_string($value)) {
            return false;
        }

        $limit = in_array($type, ['email', 'tel'], true)
            ? self::MAX_CONTACT_LENGTH
            : self::MAX_VALUE_LENGTH;

        if (mb_strlen($value, 'UTF-8') > $limit) {
            return false;
        }

        return $type !== 'email' || ($value !== '' && Validator::make(
            ['value' => $value], ['value' => ['required', 'email']]
        )->passes());
    }
}
