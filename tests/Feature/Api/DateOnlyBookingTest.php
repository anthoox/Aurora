<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateOnlyBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_date_only_booking_without_assigning_a_time(): void
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
                'booking_mode' => 'date_only',
                'first_name' => 'Ana',
                'last_name' => null,
                'email' => 'ana@example.com',
                'phone' => '600123123',
                'service_id' => $service->id,
                'booking_date' => now()->addDay()->toDateString(),
                'message' => 'Preferiblemente por la tarde.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.booking_mode', 'date_only')
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.starts_at', null)
            ->assertJsonPath('data.ends_at', null);

        $this->assertDatabaseHas('bookings', [
            'source_id' => $source->id,
            'service_id' => $service->id,
            'booking_mode' => 'date_only',
            'status' => 'pendiente',
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
                'booking_mode' => 'date_only',
                'first_name' => 'Ana',
                'email' => 'ana@example.com',
                'service_id' => $service->id,
                'booking_date' => now()->addDay()->toDateString(),
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
