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

        if ($booking->status === 'confirmada') {
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
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('starts_at')) {
            if ($booking->interaction) {
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

            if ($booking->status === 'realizada' && $booking->interaction) {
                $booking->interaction->update([
                    'status' => 'vendido',
                ]);
            }
        }

        if ($booking->wasChanged('status')) {
            if ($booking->interaction) {
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

            if ($booking->status === 'confirmada' && !$booking->google_event_id) {
                try {
                    $googleEventId = app(GoogleCalendarService::class)
                        ->createEventFromBooking($booking);

                    $booking->updateQuietly([
                        'google_event_id' => $googleEventId,
                        'google_synced_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Error al crear evento en Google Calendar al confirmar reserva', [
                        'booking_id' => $booking->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            if ($booking->status === 'cancelada' && $booking->google_event_id) {
                try {
                    app(GoogleCalendarService::class)
                        ->deleteEvent($booking->google_event_id);

                    $booking->updateQuietly([
                        'google_event_id' => null,
                        'google_synced_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Error al eliminar evento en Google Calendar al cancelar reserva', [
                        'booking_id' => $booking->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
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
