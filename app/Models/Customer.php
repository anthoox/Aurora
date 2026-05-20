<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Interaction;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Customer extends Model
{
    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'phone',
        'metadata',
        'internal_notes',
    ];
    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }



    public function Interaction(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }
}
