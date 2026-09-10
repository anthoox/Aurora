<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Source;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BookingAvailabilityService
{
    /**
     * @return Collection<int, CarbonImmutable>
     */
    public function candidateSlots(
        Source $source,
        Service $service,
        CarbonInterface $date,
    ): Collection {
        $serviceForSource = $source->services()
            ->whereKey($service->getKey())
            ->wherePivot('is_active', true)
            ->first();

        if (! $serviceForSource) {
            throw ValidationException::withMessages([
                'service_id' => 'El servicio no está disponible para esta fuente.',
            ]);
        }

        $durationMinutes = (int) $serviceForSource->pivot->duration_minutes;

        if ($durationMinutes <= 0) {
            throw ValidationException::withMessages([
                'service_id' => 'El servicio no tiene una duración configurada.',
            ]);
        }

        $slotIntervalMinutes = (int) config('bookings.slot_interval_minutes');

        if ($slotIntervalMinutes <= 0) {
            throw new \LogicException('El intervalo de generación de slots debe ser mayor que cero.');
        }

        $now = CarbonImmutable::now(config('app.timezone'));

        return $this->effectiveIntervals($source, $date)
            ->flatMap(function (array $interval) use ($durationMinutes, $slotIntervalMinutes, $now): array {
                $slots = [];

                for (
                    $slot = $interval['starts_at'];
                    $slot->addMinutes($durationMinutes)->lessThanOrEqualTo($interval['ends_at']);
                    $slot = $slot->addMinutes($slotIntervalMinutes)
                ) {
                    if ($slot->greaterThan($now)) {
                        $slots[] = $slot;
                    }
                }

                return $slots;
            })
            ->values();
    }

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
