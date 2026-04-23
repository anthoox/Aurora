<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    //
    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }
}
