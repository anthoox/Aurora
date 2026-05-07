<?php

namespace App\Observers;

use App\Models\Booking;

class BookingObserver
{
    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        if (!$booking->interaction) {
            return;
        }

        $booking->interaction->events()->create([
            'user_id' => auth()->id(),
            'type' => 'booking_created',
            'description' => 'Reserva creada',
            'new_value' => $booking->starts_at?->format('d/m/Y H:i'),
            'metadata' => [
                'booking_id' => $booking->id,
                'starts_at' => $booking->starts_at,
                'ends_at' => $booking->ends_at,
                'status' => $booking->status,
            ],
        ]);
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "deleted" event.
     */
    public function deleted(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "restored" event.
     */
    public function restored(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "force deleted" event.
     */
    public function forceDeleted(Booking $booking): void
    {
        //
    }
}
