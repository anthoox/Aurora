<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\InteractionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(InteractionObserver::class)]
class Interaction extends Model
{
    protected $fillable = ['customer_id', 'source_id', 'service_id', 'status', 'notes'];

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
}
