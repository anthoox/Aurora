<?php

namespace Tests\Feature\Validation;

use App\Rules\CustomFields;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomFieldsTest extends TestCase
{
    private static function field(string $type = 'text', mixed $value = 'Ejemplo'): array
    {
        return ['id' => 'empresa', 'label' => 'Empresa', 'type' => $type, 'value' => $value];
    }

    #[DataProvider('validPayloads')]
    public function test_valid_payloads_are_preserved(array $payload): void
    {
        $this->assertSame($payload, Validator::make($payload, CustomFields::rules())->validate());
    }

    public static function validPayloads(): iterable
    {
        yield 'omitted' => [[]];
        yield 'null' => [['custom_fields' => null]];
        yield 'empty list' => [['custom_fields' => []]];
        foreach ([
            ['text', 'Compañía Ágora 日本語'], ['text', ''],
            ['textarea', "Línea uno\nLínea dos"], ['textarea', ''],
            ['email', 'ana@example.com'], ['tel', '+34 600 123 456'], ['tel', ''],
            ['number', 0], ['number', -25], ['number', 2.5],
            ['select', 'Presupuesto'], ['select', ''],
            ['checkbox', false], ['checkbox', true],
        ] as $index => [$type, $value]) {
            yield "type {$type} {$index}" => [['custom_fields' => [self::field($type, $value)]]];
        }
        $field = self::field('text', str_repeat('ñ', CustomFields::MAX_VALUE_LENGTH));
        $field['id'] = str_repeat('i', CustomFields::MAX_ID_LENGTH);
        $field['label'] = str_repeat('é', CustomFields::MAX_LABEL_LENGTH);
        yield 'exact text limits' => [['custom_fields' => [$field]]];
        yield 'exact telephone limit' => [['custom_fields' => [self::field('tel', str_repeat('1', CustomFields::MAX_CONTACT_LENGTH))]]];
        $fields = [];
        for ($i = 0; $i < CustomFields::MAX_FIELDS; $i++) {
            $fields[] = array_replace(self::field(), ['id' => 'field_'.$i]);
        }
        yield 'exact field count' => [['custom_fields' => $fields]];
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_payloads_produce_laravel_validation_errors(mixed $fields): void
    {
        $validator = Validator::make(['custom_fields' => $fields], CustomFields::rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('custom_fields'));
        $this->expectException(ValidationException::class);
        $validator->validate();
    }

    public static function invalidPayloads(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace string' => ['   '];
        foreach (['string', 0, false, (object) [], ['empresa' => self::field()], [1 => self::field()], ['bad'], [null], [[]]] as $i => $fields) {
            yield "structure {$i}" => [$fields];
        }
        yield 'duplicate ids' => [[self::field(), self::field('number', 0)]];
        yield 'extra key' => [[self::field() + ['unexpected' => 'data']]];
        foreach (['id', 'label', 'type', 'value'] as $key) {
            $field = self::field();
            unset($field[$key]);
            yield "missing {$key}" => [[$field]];
        }
        foreach ([
            ['id', ''], ['id', null], ['id', 25], ['id', str_repeat('i', 101)],
            ['label', ''], ['label', null], ['label', false], ['label', str_repeat('é', 256)],
            ['type', 'multiselect'], ['type', []], ['type', null],
        ] as $i => [$key, $value]) {
            yield "metadata {$i}" => [[array_replace(self::field(), [$key => $value])]];
        }
        foreach ([
            ['text', null], ['text', 0], ['text', false], ['text', []], ['text', (object) []],
            ['textarea', 42], ['select', ['one']], ['tel', 123],
            ['email', 'invalid'], ['email', ''], ['email', null],
            ['number', '25'], ['number', false], ['number', null], ['number', INF], ['number', NAN],
            ['checkbox', 0], ['checkbox', 1], ['checkbox', 'false'], ['checkbox', null],
            ['text', str_repeat('ñ', 5001)], ['textarea', str_repeat('a', 5001)],
            ['select', str_repeat('a', 5001)], ['tel', str_repeat('1', 256)],
            ['email', str_repeat('a', 256).'@example.com'],
        ] as $i => [$type, $value]) {
            yield "value {$type} {$i}" => [[self::field($type, $value)]];
        }
        yield 'too many fields' => [array_map(
            fn ($i) => array_replace(self::field(), ['id' => 'field_'.$i]), range(0, 50)
        )];
    }

    public function test_rules_can_be_combined_with_existing_validation(): void
    {
        $fields = [self::field('number', 0), array_replace(self::field('checkbox', false), ['id' => 'acepta'])];
        $payload = ['message' => 'Consulta', 'custom_fields' => $fields];
        $rules = ['message' => ['required', 'string'], ...CustomFields::rules()];

        $this->assertSame($payload, Validator::make($payload, $rules)->validate());
        $this->assertTrue(Validator::make(['custom_fields' => $fields], $rules)->errors()->has('message'));
    }
}
