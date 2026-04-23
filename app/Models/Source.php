<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Source extends Model
{
    protected $fillable = ['name', 'slug', 'api_token', 'is_active'];
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }
}
