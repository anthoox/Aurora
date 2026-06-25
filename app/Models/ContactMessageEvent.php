<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessageEvent extends Model
{
    protected $fillable = [
        'contact_message_id',
        'user_id',
        'type',
        'description',
        'old_value',
        'new_value',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function contactMessage()
    {
        return $this->belongsTo(ContactMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}