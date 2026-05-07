<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{

    protected $fillable = [
        'customer_id',
        'interaction_id',
        'service_id',
        'source_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
    ];
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function interaction()
    {
        return $this->belongsTo(Interaction::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function source()
    {
        return $this->belongsTo(Source::class);
    }

    
}
