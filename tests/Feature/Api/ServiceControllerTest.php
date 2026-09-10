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
            ->assertJsonPath('data.0.duration_minutes', 30);
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
            ->assertJsonPath('data.0.duration_minutes', null);
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
