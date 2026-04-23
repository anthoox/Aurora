<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interaction extends Model
{

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
}
