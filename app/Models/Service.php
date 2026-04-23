<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Source::class);
    }
}
