<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BookingObserver
{
    public function saving(Booking $booking): void
    {
        if (
            in_array($booking->status, ['confirmada', 'realizada'], true)
            && (! $booking->starts_at || ! $booking->ends_at)
        ) {
            throw ValidationException::withMessages([
                'status' => 'Una reserva confirmada o realizada debe tener horario de inicio y fin.',
            ]);
        }

        if ($booking->starts_at && $booking->ends_at && $booking->ends_at->lessThanOrEqualTo($booking->starts_at)) {
            throw ValidationException::withMessages([
                'ends_at' => 'La hora de fin debe ser posterior a la hora de inicio.',
            ]);
        }
    }

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

        // Timeline del lead, si la reserva viene de una interacciÃ³n
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

        if ($booking->status === 'confirmada') {
            $this->syncAfterCommit($booking);
        }
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        foreach (['service_id', 'source_id', 'customer_id', 'availability_policy', 'booking_mode'] as $field) {
            if ($booking->wasChanged($field)) {
                $booking->events()->create([
                    'user_id' => auth()->id(), 'type' => $field.'_changed',
                    'description' => 'Datos de reserva actualizados: '.$field,
                    'old_value' => $booking->getOriginal($field), 'new_value' => $booking->{$field},
                ]);
            }
        }
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
                'description' => 'NÃºmero de participantes actualizado',
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
        | AutomatizaciÃ³n comercial
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

        if ($booking->wasChanged([
            'status', 'starts_at', 'ends_at', 'notes', 'service_id', 'customer_id',
            'source_id', 'participants_count', 'language', 'level',
        ]) && ($booking->status === 'confirmada' || $booking->google_event_id)) {
            $this->syncAfterCommit($booking);
        }
    }

    private function syncAfterCommit(Booking $booking): void
    {
        $id = $booking->getKey();
        DB::afterCommit(function () use ($id): void {
            try {
                // Serialize synchronization per booking and reload the committed state.
                DB::transaction(function () use ($id): void {
                    $current = Booking::query()->whereKey($id)->lockForUpdate()->first();
                    if (! $current) {
                        return;
                    }
                    if ($current->status === 'confirmada') {
                        $calendar = app(GoogleCalendarService::class);
                        if ($current->google_event_id) {
                            $calendar->updateEventFromBooking($current);
                        } else {
                            $current->google_event_id = $calendar->createEventFromBooking($current);
                        }
                        $current->google_synced_at = now();
                        $current->saveQuietly();
                    } elseif (in_array($current->status, ['cancelada', 'pendiente'], true) && $current->google_event_id) {
                        app(GoogleCalendarService::class)->deleteEvent($current->google_event_id);
                        $current->updateQuietly(['google_event_id' => null, 'google_synced_at' => now()]);
                    }
                });
            } catch (\Throwable $e) {
                Log::error('Error al sincronizar reserva con Google Calendar después del commit', [
                    'booking_id' => $id, 'message' => $e->getMessage(),
                ]);
            }
        });
    }

    public function deleting(Booking $booking): void
    {
        if (Booking::query()->whereKey($booking->getKey())->value('google_event_id')) {
            throw ValidationException::withMessages([
                'booking' => 'Cancela primero la reserva y espera a que se elimine su evento de Google Calendar.',
            ]);
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
