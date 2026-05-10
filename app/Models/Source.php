<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    protected $fillable = ['name', 'slug', 'api_token', 'is_active'];
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot([
                'description',
                'price',
                'is_active',
            ])
            ->withTimestamps();
    }



    /**
     * Leads que han entrado a través de esta web.
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }
}
