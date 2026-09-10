<?php

namespace App\Services;

use App\Models\Source;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BookingAvailabilityService
{
    /**
     * @return Collection<int, array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}>
     */
    public function effectiveIntervals(Source $source, CarbonInterface $date): Collection
    {
        $localDate = CarbonImmutable::parse(
            $date->format('Y-m-d'),
            config('app.timezone'),
        )->startOfDay();

        $exceptions = $source->availabilityExceptions()
            ->whereDate('date', $localDate->toDateString())
            ->where('is_active', true)
            ->orderBy('opens_at')
            ->get();

        if ($exceptions->contains('is_closed', true)) {
            return collect();
        }

        $periods = $exceptions->isNotEmpty()
            ? $exceptions
            : $source->openingHours()
                ->where('day_of_week', $localDate->dayOfWeekIso)
                ->where('is_active', true)
                ->orderBy('opens_at')
                ->get();

        return $periods->map(fn ($period): array => [
            'starts_at' => $this->dateTime($localDate, $period->opens_at),
            'ends_at' => $this->dateTime($localDate, $period->closes_at),
        ])->values();
    }

    private function dateTime(CarbonImmutable $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $date->toDateString().' '.$time,
            config('app.timezone'),
        );
    }
}
