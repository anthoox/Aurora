<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestOnlyBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_only_ignores_dates_and_times_even_with_a_valid_duration(): void
    {
        $source = Source::create([
            'name' => 'Web', 'slug' => 'web', 'api_token' => 'token', 'is_active' => true,
        ]);
        $service = Service::create(['name' => 'Consulta']);
        $source->services()->attach($service, ['is_active' => true, 'duration_minutes' => 30]);

        $this->withHeader('X-Aurora-Token', 'token')->postJson('/api/bookings', [
            'booking_mode' => 'request_only',
            'first_name' => 'Ana',
            'email' => 'ana@example.com',
            'service_id' => $service->id,
            'requested_date' => 'invalid-date',
            'booking_time' => 'invalid-time',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.requested_date', null)
            ->assertJsonPath('data.starts_at', null)
            ->assertJsonPath('data.ends_at', null);

        $this->assertDatabaseHas('bookings', [
            'booking_mode' => 'request_only', 'requested_date' => null,
            'starts_at' => null, 'ends_at' => null, 'status' => 'pendiente',
        ]);
    }

    public function test_existing_modes_still_require_a_date(): void
    {
        Source::create([
            'name' => 'Web', 'slug' => 'web', 'api_token' => 'token', 'is_active' => true,
        ]);

        foreach (['date_only', 'time_slots'] as $mode) {
            $this->withHeader('X-Aurora-Token', 'token')->postJson('/api/bookings', [
                'booking_mode' => $mode,
                'first_name' => 'Ana',
                'email' => 'ana@example.com',
                'service_id' => 1,
                'booking_time' => '10:00',
            ])->assertUnprocessable()->assertJsonValidationErrors('requested_date');
        }

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_it_creates_a_request_only_booking_without_assigning_a_time(): void
    {
        $source = Source::create([
            'name' => 'Web principal',
            'slug' => 'web-principal',
            'api_token' => 'valid-token',
            'is_active' => true,
        ]);

        $service = Service::create(['name' => 'Corte']);
        $source->services()->attach($service, ['is_active' => true]);

        $response = $this->withHeader('X-Aurora-Token', 'valid-token')
            ->postJson('/api/bookings', [
                'booking_mode' => 'request_only',
                'first_name' => 'Ana',
                'last_name' => null,
                'email' => 'ana@example.com',
                'phone' => '600123123',
                'service_id' => $service->id,
                'customer_message' => 'Preferiblemente por la tarde.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.booking_mode', 'request_only')
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.requested_date', null)
            ->assertJsonPath('data.starts_at', null)
            ->assertJsonPath('data.ends_at', null);

        $this->assertDatabaseHas('bookings', [
            'source_id' => $source->id,
            'service_id' => $service->id,
            'booking_mode' => 'request_only',
            'status' => 'pendiente',
            'requested_date' => null,
            'starts_at' => null,
            'ends_at' => null,
            'customer_message' => 'Preferiblemente por la tarde.',
        ]);
    }

    public function test_it_rejects_a_service_that_is_not_active_for_the_source(): void
    {
        $source = Source::create([
            'name' => 'Web principal',
            'slug' => 'web-principal',
            'api_token' => 'valid-token',
            'is_active' => true,
        ]);

        $service = Service::create(['name' => 'Corte']);
        $source->services()->attach($service, ['is_active' => false]);

        $this->withHeader('X-Aurora-Token', 'valid-token')
            ->postJson('/api/bookings', [
                'booking_mode' => 'request_only',
                'first_name' => 'Ana',
                'email' => 'ana@example.com',
                'service_id' => $service->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('service_id');
    }

    public function test_it_rejects_an_invalid_source_token(): void
    {
        $this->withHeader('X-Aurora-Token', 'invalid-token')
            ->postJson('/api/bookings', [])
            ->assertUnauthorized();
    }
}
