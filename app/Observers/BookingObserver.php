<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BookingObserver
{
    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        // Timeline propio de la reserva
        $booking->events()->create([
            'user_id' => auth()->id(),
            'type' => 'booking_created',
            'description' => 'Reserva creada',
            'new_value' => $booking->starts_at?->format('d/m/Y H:i'),
            'metadata' => [
                'status' => $booking->status,
                'starts_at' => $booking->starts_at,
                'ends_at' => $booking->ends_at,
            ],
        ]);

        // Timeline del lead, si la reserva viene de una interacción
        if ($booking->interaction) {
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

        // Google Calendar al crear confirmada
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
        /*
        |--------------------------------------------------------------------------
        | Timeline propio de la reserva
        |--------------------------------------------------------------------------
        */

        if ($booking->wasChanged('status')) {
            $booking->events()->create([
                'user_id' => auth()->id(),
                'type' => 'status_changed',
                'description' => "Estado cambiado de {$booking->getOriginal('status')} a {$booking->status}",
                'old_value' => $booking->getOriginal('status'),
                'new_value' => $booking->status,
            ]);
        }

        if ($booking->wasChanged('starts_at') || $booking->wasChanged('ends_at')) {
            $oldStartsAt = $booking->getOriginal('starts_at')
                ? Carbon::parse($booking->getOriginal('starts_at'))->format('d/m/Y H:i')
                : null;

            $newStartsAt = $booking->starts_at?->format('d/m/Y H:i');

            $oldEndsAt = $booking->getOriginal('ends_at')
                ? Carbon::parse($booking->getOriginal('ends_at'))->format('d/m/Y H:i')
                : null;

            $newEndsAt = $booking->ends_at?->format('d/m/Y H:i');

            $booking->events()->create([
                'user_id' => auth()->id(),
                'type' => 'date_changed',
                'description' => 'Fecha u horario de reserva actualizado',
                'old_value' => $oldStartsAt,
                'new_value' => $newStartsAt,
                'metadata' => [
                    'old_starts_at' => $oldStartsAt,
                    'new_starts_at' => $newStartsAt,
                    'old_ends_at' => $oldEndsAt,
                    'new_ends_at' => $newEndsAt,
                ],
            ]);
        }

        if ($booking->wasChanged('notes')) {
            $booking->events()->create([
                'user_id' => auth()->id(),
                'type' => 'notes_changed',
                'description' => 'Notas internas actualizadas',
                'old_value' => $booking->getOriginal('notes'),
                'new_value' => $booking->notes,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Timeline del lead asociado
        |--------------------------------------------------------------------------
        */

        if ($booking->interaction && ($booking->wasChanged('starts_at') || $booking->wasChanged('ends_at'))) {
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

        if ($booking->wasChanged('language')) {
            $booking->events()->create([
                'user_id' => auth()->id(),
                'type' => 'language_changed',
                'description' => 'Idioma actualizado',
                'old_value' => $booking->getOriginal('language'),
                'new_value' => $booking->language,
            ]);
        }



        if ($booking->wasChanged('level')) {
            $booking->events()->create([
                'user_id' => auth()->id(),
                'type' => 'level_changed',
                'description' => 'Nivel actualizado',
                'old_value' => $booking->getOriginal('level'),
                'new_value' => $booking->level,
            ]);
        }

        if ($booking->wasChanged('participants_count')) {
            $booking->events()->create([
                'user_id' => auth()->id(),
                'type' => 'participants_changed',
                'description' => 'Número de participantes actualizado',
                'old_value' => $booking->getOriginal('participants_count'),
                'new_value' => $booking->participants_count,
            ]);
        }

        if ($booking->interaction && $booking->wasChanged('status')) {
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

        /*
        |--------------------------------------------------------------------------
        | Automatización comercial
        |--------------------------------------------------------------------------
        */

        if ($booking->status === 'realizada' && $booking->interaction) {
            $booking->interaction->update([
                'status' => 'vendido',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Google Calendar
        |--------------------------------------------------------------------------
        */

        if ($booking->wasChanged('status') && $booking->status === 'confirmada' && !$booking->google_event_id) {
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

        if ($booking->wasChanged('status') && $booking->status === 'cancelada' && $booking->google_event_id) {
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

        if (
            $booking->google_event_id &&
            $booking->status === 'confirmada' &&
            $booking->wasChanged([
                'starts_at',
                'ends_at',
                'notes',
                'service_id',
                'customer_id',
                'source_id',
                'participants_count',
                'language',
                'level',
            ])
        ) {
            try {
                app(GoogleCalendarService::class)
                    ->updateEventFromBooking($booking);

                $booking->updateQuietly([
                    'google_synced_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Error al actualizar evento en Google Calendar', [
                    'booking_id' => $booking->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    public function deleted(Booking $booking): void
    {
        //
    }

    public function restored(Booking $booking): void
    {
        //
    }

    public function forceDeleted(Booking $booking): void
    {
        //
    }
}