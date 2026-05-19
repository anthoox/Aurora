<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ServicesSeeder::class,
            SourcesSeeder::class,
            SourceServiceSeeder::class,
        ]);
    }
}