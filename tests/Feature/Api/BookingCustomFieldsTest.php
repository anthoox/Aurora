<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Source;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingCustomFieldsTest extends TestCase
{
    use RefreshDatabase;

    private Source $source;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-10 08:00');
        CarbonImmutable::setTestNow('2026-09-10 08:00');
        $this->service = Service::create(['name' => 'Consulta']);
        $this->source = Source::create([
            'name' => 'Web de prueba', 'slug' => 'booking-test',
            'api_token' => 'booking-token', 'is_active' => true,
        ]);
        $this->source->services()->attach($this->service, ['is_active' => true, 'duration_minutes' => 30]);
        $this->source->openingHours()->create(['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '12:00']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function submit(array $overrides = []): TestResponse
    {
        return $this->withHeader('X-Aurora-Token', $this->source->api_token)
            ->postJson('/api/bookings', array_replace([
                'first_name' => 'Ana', 'email' => 'ana@example.com',
                'service_id' => $this->service->id, 'booking_mode' => 'time_slots',
                'requested_date' => '2026-09-14', 'booking_time' => '09:00',
                'customer_message' => 'Consulta original',
            ], $overrides));
    }

    private static function fields(string $company = 'Ágora 日本語'): array
    {
        return [
            ['id' => 'empresa', 'label' => 'Compañía', 'type' => 'text', 'value' => $company],
            ['id' => 'empleados', 'label' => 'Empleados', 'type' => 'number', 'value' => 0],
            ['id' => 'acepta', 'label' => 'Acepta', 'type' => 'checkbox', 'value' => false],
            ['id' => 'detalle', 'label' => 'Detalle', 'type' => 'textarea', 'value' => "Línea uno\nLínea dos"],
            ['id' => 'email', 'label' => 'Email adicional', 'type' => 'email', 'value' => 'empresa@example.com'],
            ['id' => 'tel', 'label' => 'Teléfono adicional', 'type' => 'tel', 'value' => '+34 600 123 456'],
            ['id' => 'motivo', 'label' => 'Motivo', 'type' => 'select', 'value' => 'Presupuesto'],
        ];
    }

    #[DataProvider('modePayloads')]
    public function test_fields_and_response_are_preserved_in_all_modes(string $mode, array $extra, mixed $expected): void
    {
        $response = $this->submit(['booking_mode' => $mode, ...$extra])->assertCreated();
        $booking = Booking::findOrFail($response->json('data.id'));
        $scheduled = $mode === 'time_slots';

        $response->assertExactJson([
            'message' => 'Solicitud de reserva recibida correctamente',
            'data' => [
                'id' => $booking->id, 'booking_mode' => $mode, 'status' => 'pendiente',
                'requested_date' => $mode === 'request_only' ? null : '2026-09-14',
                'starts_at' => $scheduled ? '2026-09-14 09:00:00' : null,
                'ends_at' => $scheduled ? '2026-09-14 09:30:00' : null,
                'service_id' => $this->service->id,
            ],
        ]);
        $this->assertSame($expected, $booking->custom_fields);
        $this->assertSame('Consulta original', $booking->customer_message);
        $this->assertSame('blocking', $booking->availability_policy);
        $this->assertSame($this->source->id, $booking->source_id);
        $this->assertNull($booking->google_event_id);
        $this->assertNull($booking->google_synced_at);
        $this->assertNull($booking->customer->metadata);
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    public static function modePayloads(): iterable
    {
        foreach (['request_only', 'date_only', 'time_slots'] as $mode) {
            yield "{$mode} omitted" => [$mode, [], null];
            yield "{$mode} null" => [$mode, ['custom_fields' => null], null];
            yield "{$mode} empty" => [$mode, ['custom_fields' => []], []];
            yield "{$mode} valid" => [$mode, ['custom_fields' => self::fields()], self::fields()];
        }
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_fields_return_422_without_creating_records(mixed $fields): void
    {
        $this->submit(['custom_fields' => $fields])->assertUnprocessable()
            ->assertJsonValidationErrors('custom_fields');
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('booking_events', 0);
    }

    public static function invalidPayloads(): iterable
    {
        $field = self::fields()[0];
        yield 'not a list' => ['invalid'];
        yield 'associative array' => [['empresa' => $field]];
        yield 'duplicate ids' => [[$field, $field]];
        yield 'extra key' => [[$field + ['extra' => 'data']]];
        yield 'missing value' => [[array_diff_key($field, ['value' => true])]];
        yield 'unsupported type' => [[array_replace($field, ['type' => 'unknown'])]];
        yield 'numeric string' => [[array_replace($field, ['type' => 'number', 'value' => '0'])]];
        yield 'integer checkbox' => [[array_replace($field, ['type' => 'checkbox', 'value' => 0])]];
        yield 'long value' => [[array_replace($field, ['value' => str_repeat('ñ', 5001)])]];
    }

    #[DataProvider('modes')]
    public function test_separate_valid_requests_do_not_update_customer(string $mode): void
    {
        $customer = Customer::create([
            'first_name' => 'Original', 'last_name' => 'Original', 'email' => 'ana@example.com',
            'phone' => '111111111', 'metadata' => json_encode(['empresa' => 'Original']),
            'internal_notes' => 'Notas originales',
        ])->fresh();
        $original = $customer->getRawOriginal();

        foreach (['Primera empresa', 'Segunda empresa'] as $index => $company) {
            $fields = self::fields($company);
            $response = $this->submit([
                'booking_mode' => $mode, 'first_name' => 'Nuevo nombre', 'phone' => '999999999',
                'requested_date' => $index === 0 ? '2026-09-14' : '2026-09-21',
                'custom_fields' => $fields, 'metadata' => ['empresa' => 'No guardar'],
            ])->assertCreated();
            $booking = Booking::findOrFail($response->json('data.id'));
            $this->assertSame($customer->id, $booking->customer_id);
            $this->assertSame($fields, $booking->custom_fields);
            $this->assertSame($original, $customer->fresh()->getRawOriginal());
        }

        $bookings = Booking::orderBy('id')->get();
        $this->assertSame('Primera empresa', $bookings[0]->custom_fields[0]['value']);
        $this->assertSame('Segunda empresa', $bookings[1]->custom_fields[0]['value']);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('bookings', 2);
    }

    public static function modes(): iterable
    {
        foreach (['request_only', 'date_only', 'time_slots'] as $mode) {
            yield $mode => [$mode];
        }
    }

    public function test_changed_answers_do_not_bypass_duplicates_or_availability(): void
    {
        $first = self::fields('Primera');
        $this->submit(['custom_fields' => $first])->assertCreated();
        $this->submit(['custom_fields' => self::fields('Segunda')])
            ->assertUnprocessable()->assertJsonPath('code', 'booking_already_exists');
        $this->submit(['email' => 'other@example.com', 'custom_fields' => self::fields('Otra')])
            ->assertUnprocessable()->assertJsonPath('code', 'booking_slot_unavailable');
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('customers', 1);
        $this->assertSame($first, Booking::firstOrFail()->custom_fields);

        $response = $this->submit(['booking_time' => '09:30', 'custom_fields' => self::fields('Segunda')])
            ->assertCreated();
        $this->assertSame(self::fields('Segunda'), Booking::findOrFail($response->json('data.id'))->custom_fields);
    }

    public function test_date_only_duplicates_still_ignore_custom_answers(): void
    {
        $this->submit(['booking_mode' => 'date_only', 'custom_fields' => self::fields('Primera')])->assertCreated();
        $this->submit(['booking_mode' => 'date_only', 'custom_fields' => self::fields('Segunda')])
            ->assertUnprocessable()->assertJsonPath('code', 'booking_already_exists');
        $this->assertDatabaseCount('bookings', 1);
        $this->assertSame(self::fields('Primera'), Booking::firstOrFail()->custom_fields);
    }
}
