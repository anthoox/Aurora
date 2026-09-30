<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price'];

    /**
     * Modos permitidos por el pivot cargado mediante Source::services().
     *
     * @return list<string>
     */
    public function supportedBookingModes(): array
    {
        if (! $this->pivot?->is_active) {
            return [];
        }

        return $this->hasValidBookingDuration()
            ? ['request_only', 'date_only', 'time_slots']
            : ['request_only', 'date_only'];
    }

    /**
     * Comprueba la duración del pivot de la fuente, no del servicio global.
     */
    public function hasValidBookingDuration(): bool
    {
        return (int) $this->pivot?->duration_minutes > 0;
    }

    public function sources()
    {
        return $this->belongsToMany(Source::class)
            ->withPivot([
                'description',
                'price',
                'duration_minutes',
                'is_active',
            ])
            ->withTimestamps();
    }

    /**
     * Las interacciones (leads) que han solicitado este servicio.
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
