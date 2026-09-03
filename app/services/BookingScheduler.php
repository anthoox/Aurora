<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class BookingScheduler
{
    public function scheduleAndConfirm(
        Booking $booking,
        CarbonInterface $startsAt,
        int $durationMinutes,
    ): Booking {
        if (! $booking->canBeEdited()) {
            throw ValidationException::withMessages([
                'booking' => 'Esta reserva ya no se puede modificar.',
            ]);
        }

        if ($startsAt->isPast()) {
            throw ValidationException::withMessages([
                'start_time' => 'La fecha y hora deben ser posteriores al momento actual.',
            ]);
        }

        if ($durationMinutes <= 0) {
            throw ValidationException::withMessages([
                'duration_minutes' => 'La duración debe ser mayor que cero.',
            ]);
        }

        $booking->update([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($durationMinutes),
            'status' => 'confirmada',
        ]);

        return $booking->refresh();
    }
}
