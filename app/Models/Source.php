<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    protected $fillable = ['name', 'slug', 'api_token', 'is_active'];

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot([
                'description',
                'price',
                'duration_minutes',
                'is_active',
            ])
            ->withTimestamps();
    }

    /**
     * Leads que han entrado a través de esta web.
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(SourceOpeningHour::class);
    }

    public function availabilityExceptions(): HasMany
    {
        return $this->hasMany(SourceAvailabilityException::class);
    }
}
