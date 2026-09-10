<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Source;
use App\Services\BookingAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_it_generates_candidate_slots_that_fit_the_service_duration(): void
    {
        $source = $this->source();
        $service = $this->serviceForSource($source, 60);
        $source->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '09:00',
            'closes_at' => '10:30',
        ]);

        $slots = $this->service()->candidateSlots(
            $source,
            $service,
            CarbonImmutable::parse('2026-09-14'),
        );

        $this->assertSame([
            '09:00',
            '09:30',
        ], $slots->map->format('H:i')->all());
    }

    public function test_it_uses_the_duration_configured_for_the_specific_source(): void
    {
        $shortSource = $this->source('short');
        $longSource = $this->source('long');
        $service = Service::create(['name' => 'Corte']);

        $shortSource->services()->attach($service, ['duration_minutes' => 30, 'is_active' => true]);
        $longSource->services()->attach($service, ['duration_minutes' => 60, 'is_active' => true]);

        foreach ([$shortSource, $longSource] as $source) {
            $source->openingHours()->create([
                'day_of_week' => 1,
                'opens_at' => '09:00',
                'closes_at' => '10:30',
            ]);
        }

        $date = CarbonImmutable::parse('2026-09-14');

        $this->assertSame(
            ['09:00', '09:30', '10:00'],
            $this->service()->candidateSlots($shortSource, $service, $date)->map->format('H:i')->all(),
        );

        $this->assertSame(
            ['09:00', '09:30'],
            $this->service()->candidateSlots($longSource, $service, $date)->map->format('H:i')->all(),
        );
    }

    public function test_a_service_without_duration_cannot_generate_slots(): void
    {
        $source = $this->source();
        $service = $this->serviceForSource($source, null);

        $this->expectException(ValidationException::class);

        $this->service()->candidateSlots(
            $source,
            $service,
            CarbonImmutable::parse('2026-09-14'),
        );
    }

    public function test_an_inactive_service_cannot_generate_slots(): void
    {
        $source = $this->source();
        $service = Service::create(['name' => 'Corte']);
        $source->services()->attach($service, [
            'duration_minutes' => 30,
            'is_active' => false,
        ]);

        $this->expectException(ValidationException::class);

        $this->service()->candidateSlots(
            $source,
            $service,
            CarbonImmutable::parse('2026-09-14'),
        );
    }

    public function test_past_slots_are_not_returned(): void
    {
        CarbonImmutable::setTestNow('2026-09-14 10:05:00');

        try {
            $source = $this->source();
            $service = $this->serviceForSource($source, 30);
            $source->openingHours()->create([
                'day_of_week' => 1,
                'opens_at' => '09:00',
                'closes_at' => '12:00',
            ]);

            $slots = $this->service()->candidateSlots(
                $source,
                $service,
                CarbonImmutable::parse('2026-09-14'),
            );

            $this->assertSame([
                '10:30',
                '11:00',
                '11:30',
            ], $slots->map->format('H:i')->all());
        } finally {
            CarbonImmutable::setTestNow();
        }
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

    private function serviceForSource(Source $source, ?int $durationMinutes): Service
    {
        $service = Service::create(['name' => 'Corte']);
        $source->services()->attach($service, [
            'duration_minutes' => $durationMinutes,
            'is_active' => true,
        ]);

        return $service;
    }

    private function source(string $suffix = 'main'): Source
    {
        return Source::create([
            'name' => "Web {$suffix}",
            'slug' => "web-{$suffix}",
            'api_token' => "token-{$suffix}",
            'is_active' => true,
        ]);
    }
}
