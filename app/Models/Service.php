<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Service extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'price'];
    public function sources()
    {
        return $this->belongsToMany(Source::class);
    }
}
