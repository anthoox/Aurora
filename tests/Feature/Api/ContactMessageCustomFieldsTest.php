<?php

namespace Tests\Feature\Api;

use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactMessageCustomFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Source::create([
            'name' => 'Web de prueba', 'slug' => 'contact-test',
            'api_token' => 'contact-token', 'is_active' => true,
        ]);
    }

    private function submit(array $overrides = []): TestResponse
    {
        return $this->withHeader('X-Aurora-Token', 'contact-token')
            ->postJson('/api/contact-messages', array_replace([
                'first_name' => 'Ana', 'last_name' => 'García',
                'email' => 'ana@example.com', 'phone' => '+34 600 123 456',
                'message' => 'Consulta de prueba',
            ], $overrides));
    }

    #[DataProvider('optionalFields')]
    public function test_optional_fields_preserve_existing_behavior(array $overrides, mixed $expected): void
    {
        $response = $this->submit($overrides)->assertCreated();
        $message = ContactMessage::findOrFail($response->json('contact_message_id'));

        $response->assertExactJson([
            'message' => 'Mensaje de contacto recibido correctamente',
            'contact_message_id' => $message->id,
        ]);
        $this->assertSame($expected, $message->custom_fields);
        $this->assertSame('nuevo', $message->status);
        $this->assertSame('Consulta de prueba', $message->message);
        $this->assertSame('Ana', $message->first_name);
        $this->assertSame('García', $message->last_name);
        $this->assertSame('ana@example.com', $message->email);
        $this->assertSame('+34 600 123 456', $message->phone);
        $this->assertSame(Source::firstOrFail()->id, $message->source_id);
        $this->assertSame(Customer::firstOrFail()->id, $message->customer_id);
        $this->assertNull($message->customer->metadata);
        $this->assertDatabaseCount('contact_messages', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    public static function optionalFields(): iterable
    {
        yield 'omitted' => [[], null];
        yield 'null' => [['custom_fields' => null], null];
        yield 'empty list' => [['custom_fields' => []], []];
    }

    public function test_valid_fields_preserve_json_types_and_unicode(): void
    {
        $fields = [
            ['id' => 'empresa', 'label' => 'Compañía', 'type' => 'text', 'value' => 'Ágora 日本語'],
            ['id' => 'empleados', 'label' => 'Empleados', 'type' => 'number', 'value' => 0],
            ['id' => 'acepta', 'label' => 'Acepta', 'type' => 'checkbox', 'value' => false],
            ['id' => 'detalle', 'label' => 'Detalle', 'type' => 'textarea', 'value' => "Primera línea\nSegunda línea"],
            ['id' => 'email', 'label' => 'Email adicional', 'type' => 'email', 'value' => 'empresa@example.com'],
            ['id' => 'tel', 'label' => 'Teléfono adicional', 'type' => 'tel', 'value' => '+34 999 123 456'],
            ['id' => 'motivo', 'label' => 'Motivo', 'type' => 'select', 'value' => 'Presupuesto'],
        ];

        $response = $this->submit(['custom_fields' => $fields])->assertCreated();
        $message = ContactMessage::findOrFail($response->json('contact_message_id'));

        $this->assertSame($fields, $message->custom_fields);
        $response->assertExactJson([
            'message' => 'Mensaje de contacto recibido correctamente',
            'contact_message_id' => $message->id,
        ]);
        $this->assertNull($message->customer->metadata);
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_fields_are_rejected_before_any_records_are_created(mixed $fields): void
    {
        $this->submit(['custom_fields' => $fields])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('custom_fields');

        $this->assertDatabaseCount('contact_messages', 0);
        $this->assertDatabaseCount('customers', 0);
    }

    public static function invalidFields(): iterable
    {
        $field = ['id' => 'empresa', 'label' => 'Empresa', 'type' => 'text', 'value' => 'Ejemplo'];
        yield 'not a list' => ['invalid'];
        yield 'associative array' => [['empresa' => $field]];
        yield 'duplicate ids' => [[$field, $field]];
        yield 'extra key' => [[$field + ['extra' => 'unexpected']]];
        yield 'missing value' => [[array_diff_key($field, ['value' => true])]];
        yield 'unsupported type' => [[array_replace($field, ['type' => 'unknown'])]];
        yield 'numeric string' => [[array_replace($field, ['type' => 'number', 'value' => '0'])]];
        yield 'integer checkbox' => [[array_replace($field, ['type' => 'checkbox', 'value' => 0])]];
        yield 'invalid email' => [[array_replace($field, ['type' => 'email', 'value' => 'invalid'])]];
        yield 'long value' => [[array_replace($field, ['value' => str_repeat('ñ', 5001)])]];
        yield 'too many fields' => [array_map(
            fn ($i) => array_replace($field, ['id' => 'field_'.$i]), range(0, 50)
        )];
    }

    public function test_two_messages_keep_separate_answers_without_updating_the_customer(): void
    {
        $customer = Customer::create([
            'first_name' => 'Nombre original', 'last_name' => 'Apellidos originales',
            'email' => 'ana@example.com', 'phone' => '111111111',
            'metadata' => json_encode(['empresa' => 'Ficha original']),
            'internal_notes' => 'Notas originales',
        ])->fresh();
        $original = $customer->getRawOriginal();

        foreach (['Empresa primera', 'Empresa segunda'] as $index => $answer) {
            $fields = [['id' => 'empresa', 'label' => 'Empresa', 'type' => 'text', 'value' => $answer]];
            $response = $this->submit([
                'first_name' => 'Nombre enviado '.$index,
                'custom_fields' => $fields,
                'metadata' => ['empresa' => 'No debe guardarse'],
            ])->assertCreated();
            $message = ContactMessage::findOrFail($response->json('contact_message_id'));

            $this->assertSame($customer->id, $message->customer_id);
            $this->assertSame('Nombre enviado '.$index, $message->first_name);
            $this->assertSame($fields, $message->custom_fields);
            $this->assertSame($original, $customer->fresh()->getRawOriginal());
        }

        $messages = ContactMessage::orderBy('id')->get();
        $this->assertSame('Empresa primera', $messages[0]->custom_fields[0]['value']);
        $this->assertSame('Empresa segunda', $messages[1]->custom_fields[0]['value']);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('contact_messages', 2);
    }

    public function test_existing_required_fields_are_still_validated(): void
    {
        $this->submit(['message' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('message');

        $this->assertDatabaseCount('contact_messages', 0);
        $this->assertDatabaseCount('customers', 0);
    }
}
