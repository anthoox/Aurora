<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Source;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-09-10 08:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_the_available_slots_for_the_authenticated_source(): void
    {
        [$source, $service] = $this->sourceAndService();
        $source->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '09:00',
            'closes_at' => '11:00',
        ]);
        $this->blockingBooking($source, '2026-09-14 09:30', '2026-09-14 10:00');

        $this->withHeader('X-Aurora-Token', $source->api_token)
            ->getJson("/api/availability?service_id={$service->id}&date=2026-09-14")
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'date' => '2026-09-14',
                    'service_id' => $service->id,
                    'duration_minutes' => 30,
                    'slots' => ['09:00', '10:00', '10:30'],
                ],
            ]);
    }

    public function test_it_returns_an_empty_list_for_a_closed_date(): void
    {
        [$source, $service] = $this->sourceAndService();
        $source->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '09:00',
            'closes_at' => '11:00',
        ]);
        $source->availabilityExceptions()->create([
            'date' => '2026-09-14',
            'is_closed' => true,
        ]);

        $this->withHeader('X-Aurora-Token', $source->api_token)
            ->getJson("/api/availability?service_id={$service->id}&date=2026-09-14")
            ->assertOk()
            ->assertJsonPath('data.slots', []);
    }

    public function test_it_rejects_an_invalid_source_token(): void
    {
        $this->withHeader('X-Aurora-Token', 'invalid-token')
            ->getJson('/api/availability?service_id=1&date=2026-09-14')
            ->assertUnauthorized();
    }

    public function test_it_rejects_a_service_from_another_source(): void
    {
        $source = $this->source('main');
        [, $otherService] = $this->sourceAndService('other');

        $this->withHeader('X-Aurora-Token', $source->api_token)
            ->getJson("/api/availability?service_id={$otherService->id}&date=2026-09-14")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('service_id');
    }

    public function test_it_rejects_a_service_without_duration(): void
    {
        [$source, $service] = $this->sourceAndService(durationMinutes: null);

        $this->withHeader('X-Aurora-Token', $source->api_token)
            ->getJson("/api/availability?service_id={$service->id}&date=2026-09-14")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('service_id');
    }

    private function blockingBooking(Source $source, string $startsAt, string $endsAt): Booking
    {
        $customer = Customer::create([
            'first_name' => 'Ana',
            'email' => 'ana@example.com',
        ]);

        return Booking::withoutEvents(fn (): Booking => Booking::create([
            'customer_id' => $customer->id,
            'source_id' => $source->id,
            'booking_mode' => 'time_slots',
            'requested_date' => '2026-09-14',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'confirmada',
        ]));
    }

    private function sourceAndService(
        string $suffix = 'main',
        ?int $durationMinutes = 30,
    ): array {
        $source = $this->source($suffix);
        $service = Service::create(['name' => "Corte {$suffix}"]);
        $source->services()->attach($service, [
            'duration_minutes' => $durationMinutes,
            'is_active' => true,
        ]);

        return [$source, $service];
    }

    private function source(string $suffix): Source
    {
        return Source::create([
            'name' => "Web {$suffix}",
            'slug' => "web-{$suffix}",
            'api_token' => "token-{$suffix}",
            'is_active' => true,
        ]);
    }
}
