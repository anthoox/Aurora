<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Source;
use Illuminate\Support\Str;

class SourcesSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            'Flamenco Sevilla Class',
            'TuriFlamenco',
            'Airbnb',
        ];

        foreach ($sources as $source) {
            Source::create([
                'name' => $source,
                'slug' => Str::slug($source),
                'is_active' => true,
                'api_token' => Str::random(40),
            ]);
        }
    }
}