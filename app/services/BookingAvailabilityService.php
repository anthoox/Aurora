<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Source;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BookingAvailabilityService
{
    /**
     * @return array{duration_minutes: int, slots: Collection<int, CarbonImmutable>}
     */
    public function availabilityFor(
        Source $source,
        Service $service,
        CarbonInterface $date,
    ): array {
        $durationMinutes = $this->durationMinutesFor($source, $service);

        return [
            'duration_minutes' => $durationMinutes,
            'slots' => $this->availableSlotsForDuration($source, $date, $durationMinutes),
        ];
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    public function availableSlots(
        Source $source,
        Service $service,
        CarbonInterface $date,
    ): Collection {
        $durationMinutes = $this->durationMinutesFor($source, $service);

        return $this->availableSlotsForDuration($source, $date, $durationMinutes);
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    private function availableSlotsForDuration(
        Source $source,
        CarbonInterface $date,
        int $durationMinutes,
    ): Collection {
        $slots = $this->candidateSlotsForDuration($source, $date, $durationMinutes);

        if ($slots->isEmpty()) {
            return $slots;
        }

        $rangeStart = $slots->first();
        $rangeEnd = $slots->last()->addMinutes($durationMinutes);

        $blockingBookings = $source->bookings()
            ->whereIn('status', Booking::AVAILABILITY_BLOCKING_STATUSES)
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->where('starts_at', '<', $rangeEnd)
            ->where('ends_at', '>', $rangeStart)
            ->get(['starts_at', 'ends_at']);

        return $slots
            ->reject(function (CarbonImmutable $slot) use ($blockingBookings, $durationMinutes): bool {
                $slotEnd = $slot->addMinutes($durationMinutes);

                return $blockingBookings->contains(
                    fn (Booking $booking): bool => $booking->starts_at->lessThan($slotEnd)
                        && $booking->ends_at->greaterThan($slot),
                );
            })
            ->values();
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    public function candidateSlots(
        Source $source,
        Service $service,
        CarbonInterface $date,
    ): Collection {
        $durationMinutes = $this->durationMinutesFor($source, $service);

        return $this->candidateSlotsForDuration($source, $date, $durationMinutes);
    }

    private function durationMinutesFor(Source $source, Service $service): int
    {
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

        return $durationMinutes;
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    private function candidateSlotsForDuration(
        Source $source,
        CarbonInterface $date,
        int $durationMinutes,
    ): Collection {

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
