<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Services\BookingAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_active_weekly_intervals_for_the_requested_day(): void
    {
        $source = $this->source();

        $source->openingHours()->createMany([
            [
                'day_of_week' => 1,
                'opens_at' => '09:00',
                'closes_at' => '14:00',
            ],
            [
                'day_of_week' => 1,
                'opens_at' => '16:00',
                'closes_at' => '20:00',
            ],
            [
                'day_of_week' => 2,
                'opens_at' => '10:00',
                'closes_at' => '13:00',
            ],
        ]);

        $intervals = $this->service()->effectiveIntervals(
            $source,
            CarbonImmutable::parse('2026-09-14'),
        );

        $this->assertSame([
            ['starts_at' => '2026-09-14 09:00', 'ends_at' => '2026-09-14 14:00'],
            ['starts_at' => '2026-09-14 16:00', 'ends_at' => '2026-09-14 20:00'],
        ], $this->formatted($intervals));
    }

    public function test_special_intervals_replace_the_weekly_schedule_for_the_date(): void
    {
        $source = $this->source();
        $source->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '09:00',
            'closes_at' => '20:00',
        ]);
        $source->availabilityExceptions()->createMany([
            [
                'date' => '2026-09-14',
                'opens_at' => '10:00',
                'closes_at' => '13:00',
            ],
            [
                'date' => '2026-09-14',
                'opens_at' => '17:00',
                'closes_at' => '19:00',
            ],
        ]);

        $intervals = $this->service()->effectiveIntervals(
            $source,
            CarbonImmutable::parse('2026-09-14'),
        );

        $this->assertSame([
            ['starts_at' => '2026-09-14 10:00', 'ends_at' => '2026-09-14 13:00'],
            ['starts_at' => '2026-09-14 17:00', 'ends_at' => '2026-09-14 19:00'],
        ], $this->formatted($intervals));
    }

    public function test_a_closed_exception_returns_no_intervals(): void
    {
        $source = $this->source();
        $source->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '09:00',
            'closes_at' => '20:00',
        ]);
        $source->availabilityExceptions()->create([
            'date' => '2026-09-14',
            'is_closed' => true,
        ]);

        $intervals = $this->service()->effectiveIntervals(
            $source,
            CarbonImmutable::parse('2026-09-14'),
        );

        $this->assertTrue($intervals->isEmpty());
    }

    public function test_inactive_exceptions_are_ignored(): void
    {
        $source = $this->source();
        $source->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '09:00',
            'closes_at' => '14:00',
        ]);
        $source->availabilityExceptions()->create([
            'date' => '2026-09-14',
            'is_closed' => true,
            'is_active' => false,
        ]);

        $intervals = $this->service()->effectiveIntervals(
            $source,
            CarbonImmutable::parse('2026-09-14'),
        );

        $this->assertSame([
            ['starts_at' => '2026-09-14 09:00', 'ends_at' => '2026-09-14 14:00'],
        ], $this->formatted($intervals));
    }

    private function formatted($intervals): array
    {
        return $intervals
            ->map(fn (array $interval): array => [
                'starts_at' => $interval['starts_at']->format('Y-m-d H:i'),
                'ends_at' => $interval['ends_at']->format('Y-m-d H:i'),
            ])
            ->all();
    }

    private function service(): BookingAvailabilityService
    {
        return app(BookingAvailabilityService::class);
    }

    private function source(): Source
    {
        return Source::create([
            'name' => 'Web principal',
            'slug' => 'web-principal',
            'api_token' => 'valid-token',
            'is_active' => true,
        ]);
    }
}
