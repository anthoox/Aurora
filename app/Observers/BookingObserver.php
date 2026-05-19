<?php

namespace App\Observers;

use App\Models\Booking;


use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;
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

        try {
            $googleEventId = app(GoogleCalendarService::class)
                ->createEventFromBooking($booking);

            $booking->updateQuietly([
                'google_event_id' => $googleEventId,
                'google_synced_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al crear evento en Google Calendar', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        if (!$booking->interaction) {
            return;
        }

        if ($booking->wasChanged('starts_at')) {
            $booking->interaction->events()->create([
                'user_id' => auth()->id(),
                'type' => 'booking_updated',
                'description' => 'Fecha de reserva actualizada',
                'old_value' => $booking->getOriginal('starts_at'),
                'new_value' => $booking->starts_at?->format('d/m/Y H:i'),
                'metadata' => [
                    'booking_id' => $booking->id,
                    'field' => 'starts_at',
                ],
            ]);
        }

        if ($booking->wasChanged('status')) {
            $booking->interaction->events()->create([
                'user_id' => auth()->id(),
                'type' => 'booking_status_changed',
                'description' => "Estado de reserva cambiado de {$booking->getOriginal('status')} a {$booking->status}",
                'old_value' => $booking->getOriginal('status'),
                'new_value' => $booking->status,
                'metadata' => [
                    'booking_id' => $booking->id,
                    'field' => 'status',
                ],
            ]);
        }
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
