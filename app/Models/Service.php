<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price'];

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
