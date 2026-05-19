<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Source;
use App\Models\Service;

class SourceServiceSeeder extends Seeder
{
    public function run(): void
    {
        $flamenco = Source::where('name', 'Flamenco Sevilla Class')->first();
        $turi = Source::where('name', 'TuriFlamenco')->first();
        $airbnb = Source::where('name', 'Airbnb')->first();

        $cante = Service::where('name', 'Cante')->first();
        $guitarra = Service::where('name', 'Guitarra')->first();
        $baile = Service::where('name', 'Baile')->first();
        $cajon = Service::where('name', 'Cajón')->first();
        $historia = Service::where('name', 'Historia del flamenco')->first();

        $flamenco->services()->attach($cante->id, [
            'price' => 20,
            'description' => 'Clase personalizada de cante flamenco',
            'is_active' => true,
        ]);

        $flamenco->services()->attach($guitarra->id, [
            'price' => 25,
            'description' => 'Clase de guitarra flamenca',
            'is_active' => true,
        ]);

        $turi->services()->attach($baile->id, [
            'price' => 35,
            'description' => 'Experiencia turística de baile flamenco',
            'is_active' => true,
        ]);

        $turi->services()->attach($historia->id, [
            'price' => 15,
            'description' => 'Historia y cultura del flamenco',
            'is_active' => true,
        ]);

        $airbnb->services()->attach($cante->id, [
            'price' => 40,
            'description' => 'Experiencia Airbnb de cante flamenco',
            'is_active' => true,
        ]);

        $airbnb->services()->attach($cajon->id, [
            'price' => 30,
            'description' => 'Taller de cajón flamenco',
            'is_active' => true,
        ]);
    }
}