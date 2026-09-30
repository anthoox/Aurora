<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_source_specific_service_duration(): void
    {
        $source = $this->source();
        $service = Service::create(['name' => 'Corte']);

        $source->services()->attach($service, [
            'is_active' => true,
            'duration_minutes' => 30,
        ]);

        $this->withHeader('X-Aurora-Token', $source->api_token)
            ->getJson('/api/services')
            ->assertOk()
            ->assertJsonPath('data.0.id', $service->id)
            ->assertJsonPath('data.0.duration_minutes', 30)
            ->assertJsonPath('data.0.supported_booking_modes', ['date_only', 'time_slots'])
            ->assertJsonStructure(['data' => [['id', 'name', 'description', 'price', 'duration_minutes', 'supported_booking_modes']]]);
    }

    public function test_a_service_without_duration_remains_available_for_date_only_forms(): void
    {
        $source = $this->source();
        $service = Service::create(['name' => 'Consulta']);

        $source->services()->attach($service, [
            'is_active' => true,
            'duration_minutes' => null,
        ]);

        $this->withHeader('X-Aurora-Token', $source->api_token)
            ->getJson('/api/services')
            ->assertOk()
            ->assertJsonPath('data.0.id', $service->id)
            ->assertJsonPath('data.0.duration_minutes', null)
            ->assertJsonPath('data.0.supported_booking_modes', ['date_only']);
    }

    public function test_booking_modes_use_the_authenticated_sources_pivot(): void
    {
        $service = Service::create(['name' => 'Consulta compartida']);

        foreach ([30, null, 0] as $index => $duration) {
            $source = Source::create([
                'name' => "Web {$index}",
                'slug' => "web-{$index}",
                'api_token' => "token-{$index}",
                'is_active' => true,
            ]);
            $source->services()->attach($service, [
                'is_active' => true,
                'duration_minutes' => $duration,
                'description' => "Descripción {$index}",
                'price' => '25.00',
            ]);
        }

        foreach ([30, null, 0] as $index => $duration) {
            $source = Source::where('api_token', "token-{$index}")->firstOrFail();
            $catalogService = $source->services()->findOrFail($service->id);

            $this->withHeader('X-Aurora-Token', "token-{$index}")
                ->getJson('/api/services')
                ->assertOk()
                ->assertExactJson(['data' => [[
                    'id' => $service->id,
                    'name' => 'Consulta compartida',
                    'description' => "Descripción {$index}",
                    'price' => $catalogService->pivot->price,
                    'duration_minutes' => $duration,
                    'supported_booking_modes' => $index === 0
                        ? ['date_only', 'time_slots']
                        : ['date_only'],
                ]]]);
        }
    }

    public function test_it_excludes_inactive_and_unlinked_services(): void
    {
        $source = $this->source();
        $inactive = Service::create(['name' => 'Inactivo']);
        Service::create(['name' => 'Sin vincular']);
        $source->services()->attach($inactive, [
            'is_active' => false,
            'duration_minutes' => 30,
        ]);

        $this->withHeader('X-Aurora-Token', $source->api_token)
            ->getJson('/api/services')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    private function source(): Source
    {
        return Source::create([
            'name' => 'Web principal',
            'slug' => 'web-principal',
            'api_token' => 'valid-token',
            'is_active' => true,
        ]);
    }
}
