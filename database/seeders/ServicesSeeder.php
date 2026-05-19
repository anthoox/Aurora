<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            'Cante',
            'Guitarra',
            'Baile',
            'Cajón',
            'Historia del flamenco',
        ];

        foreach ($services as $service) {
            Service::create([
                'name' => $service,
            ]);
        }
    }
}