<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class SourceAvailabilityException extends Model
{
    protected $fillable = [
        'source_id',
        'date',
        'is_closed',
        'opens_at',
        'closes_at',
        'is_active',
    ];

    protected $attributes = [
        'is_closed' => false,
        'is_active' => true,
    ];

    protected $casts = [
        'date' => 'date',
        'is_closed' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $availabilityException): void {
            if (! $availabilityException->date) {
                throw ValidationException::withMessages([
                    'date' => 'La excepción debe tener una fecha.',
                ]);
            }

            if ($availabilityException->is_closed) {
                $availabilityException->opens_at = null;
                $availabilityException->closes_at = null;
            } elseif (! $availabilityException->opens_at || ! $availabilityException->closes_at) {
                throw ValidationException::withMessages([
                    'opens_at' => 'Un horario especial debe tener hora de apertura y cierre.',
                ]);
            } elseif ($availabilityException->closes_at <= $availabilityException->opens_at) {
                throw ValidationException::withMessages([
                    'closes_at' => 'La hora de cierre debe ser posterior a la hora de apertura.',
                ]);
            }

            if (! $availabilityException->is_active) {
                return;
            }

            $exceptionsForDate = self::query()
                ->where('source_id', $availabilityException->source_id)
                ->whereDate('date', $availabilityException->date)
                ->where('is_active', true)
                ->when(
                    $availabilityException->exists,
                    fn ($query) => $query->whereKeyNot($availabilityException->getKey()),
                );

            if ($availabilityException->is_closed && (clone $exceptionsForDate)->exists()) {
                throw ValidationException::withMessages([
                    'is_closed' => 'Un día cerrado no puede tener otros horarios especiales activos.',
                ]);
            }

            if (! $availabilityException->is_closed && (clone $exceptionsForDate)->where('is_closed', true)->exists()) {
                throw ValidationException::withMessages([
                    'date' => 'La fecha está marcada como cerrada.',
                ]);
            }

            $overlaps = ! $availabilityException->is_closed
                && (clone $exceptionsForDate)
                    ->where('is_closed', false)
                    ->where('opens_at', '<', $availabilityException->closes_at)
                    ->where('closes_at', '>', $availabilityException->opens_at)
                    ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'opens_at' => 'Este tramo especial se solapa con otro de la misma fecha.',
                ]);
            }
        });
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
