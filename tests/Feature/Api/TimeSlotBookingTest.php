<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Source;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeSlotBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-10 08:00:00');
        CarbonImmutable::setTestNow('2026-09-10 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_creates_and_confirms_an_available_time_slot_booking(): void
    {
        [$source, $service] = $this->bookableService();

        $response = $this->withHeader('X-Aurora-Token', $source->api_token)
            ->postJson('/api/bookings', $this->payload($service, '09:30'));

        $response
            ->assertCreated()
            ->assertJsonPath('data.booking_mode', 'time_slots')
            ->assertJsonPath('data.status', 'confirmada')
            ->assertJsonPath('data.starts_at', '2026-09-14 09:30:00')
            ->assertJsonPath('data.ends_at', '2026-09-14 10:00:00');

        $this->assertDatabaseHas('bookings', [
            'source_id' => $source->id,
            'service_id' => $service->id,
            'booking_mode' => 'time_slots',
            'status' => 'confirmada',
            'starts_at' => '2026-09-14 09:30:00',
            'ends_at' => '2026-09-14 10:00:00',
        ]);
    }

    public function test_the_same_slot_is_rechecked_before_creating_a_second_booking(): void
    {
        [$source, $service] = $this->bookableService();
        $payload = $this->payload($service, '09:30');

        $this->withHeader('X-Aurora-Token', $source->api_token)
            ->postJson('/api/bookings', $payload)
            ->assertCreated();

        $this->withHeader('X-Aurora-Token', $source->api_token)
            ->postJson('/api/bookings', [
                ...$payload,
                'email' => 'segunda@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('booking_time');

        $this->assertSame(1, Booking::count());
    }

    public function test_it_rejects_a_time_that_is_not_an_available_slot(): void
    {
        [$source, $service] = $this->bookableService();

        $this->withHeader('X-Aurora-Token', $source->api_token)
            ->postJson('/api/bookings', $this->payload($service, '09:15'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('booking_time');
    }

    public function test_booking_time_is_required_only_for_time_slots_mode(): void
    {
        [$source, $service] = $this->bookableService();
        $payload = $this->payload($service, '09:30');
        unset($payload['booking_time']);

        $this->withHeader('X-Aurora-Token', $source->api_token)
            ->postJson('/api/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('booking_time');
    }

    private function bookableService(): array
    {
        $source = Source::create([
            'name' => 'Web principal',
            'slug' => 'web-principal',
            'api_token' => 'valid-token',
            'is_active' => true,
        ]);
        $service = Service::create(['name' => 'Corte']);

        $source->services()->attach($service, [
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
        $source->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '09:00',
            'closes_at' => '11:00',
        ]);

        return [$source, $service];
    }

    private function payload(Service $service, string $bookingTime): array
    {
        return [
            'booking_mode' => 'time_slots',
            'first_name' => 'Ana',
            'last_name' => 'García',
            'email' => 'ana@example.com',
            'phone' => '600123123',
            'service_id' => $service->id,
            'requested_date' => '2026-09-14',
            'booking_time' => $bookingTime,
            'customer_message' => 'Primera hora disponible.',
        ];
    }
}
