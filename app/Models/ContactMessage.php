<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\ContactMessageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(ContactMessageObserver::class)]
class ContactMessage extends Model
{
    protected $fillable = [
        'customer_id',
        'source_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'message',
        'custom_fields',
        'status',
        'responded_at',
        'converted_at',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'responded_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function source()
    {
        return $this->belongsTo(Source::class);
    }

    public function events()
    {
        return $this->hasMany(ContactMessageEvent::class);
    }
}
