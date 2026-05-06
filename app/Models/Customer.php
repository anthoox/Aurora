<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['email', 'first_name', 'last_name', 'phone', 'metadata'];
    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
