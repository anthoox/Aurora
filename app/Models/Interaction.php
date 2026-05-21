<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Interaction extends Model
{
    protected $fillable = [
        'customer_id',
        'source_id',
        'service_id',
        'status',
        'origin_type',
        'message',
        'notes',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function source()
    {
        return $this->belongsTo(Source::class);
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(InteractionEvent::class);
    }
    public function getCatalogPriceAttribute(): ?float
    {
        if (!$this->source_id || !$this->service_id) {
            return null;
        }

        $service = $this->source
                ?->services()
            ->where('services.id', $this->service_id)
            ->first();

        return $service?->pivot?->price !== null
            ? (float) $service->pivot->price
            : null;
    }

    public function getCatalogDescriptionAttribute(): ?string
    {
        if (!$this->source_id || !$this->service_id) {
            return null;
        }

        $service = $this->source
                ?->services()
            ->where('services.id', $this->service_id)
            ->first();

        return $service?->pivot?->description;
    }

    public function canBeEdited(): bool
    {
        return $this->status !== 'vendido';
    }
    
}
