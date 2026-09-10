<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class SourceOpeningHour extends Model
{
    protected $fillable = [
        'source_id',
        'day_of_week',
        'opens_at',
        'closes_at',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $openingHour): void {
            if (! in_array($openingHour->day_of_week, range(1, 7), true)) {
                throw ValidationException::withMessages([
                    'day_of_week' => 'El día de la semana debe estar comprendido entre 1 y 7.',
                ]);
            }

            if (! $openingHour->opens_at || ! $openingHour->closes_at) {
                throw ValidationException::withMessages([
                    'opens_at' => 'El horario debe tener hora de apertura y cierre.',
                ]);
            }

            if ($openingHour->closes_at <= $openingHour->opens_at) {
                throw ValidationException::withMessages([
                    'closes_at' => 'La hora de cierre debe ser posterior a la hora de apertura.',
                ]);
            }

            if (! $openingHour->is_active) {
                return;
            }

            $overlaps = self::query()
                ->where('source_id', $openingHour->source_id)
                ->where('day_of_week', $openingHour->day_of_week)
                ->where('is_active', true)
                ->when(
                    $openingHour->exists,
                    fn ($query) => $query->whereKeyNot($openingHour->getKey()),
                )
                ->where('opens_at', '<', $openingHour->closes_at)
                ->where('closes_at', '>', $openingHour->opens_at)
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'opens_at' => 'Este tramo se solapa con otro horario activo del mismo día.',
                ]);
            }
        });
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
