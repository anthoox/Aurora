<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Models\SourceOpeningHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SourceOpeningHourTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_source_can_have_split_opening_hours_for_the_same_day(): void
    {
        $source = $this->source();

        $source->openingHours()->createMany([
            [
                'day_of_week' => 1,
                'opens_at' => '09:00',
                'closes_at' => '14:00',
                'is_active' => true,
            ],
            [
                'day_of_week' => 1,
                'opens_at' => '16:00',
                'closes_at' => '20:00',
                'is_active' => true,
            ],
        ]);

        $this->assertCount(2, $source->openingHours);
    }

    public function test_closing_time_must_be_after_opening_time(): void
    {
        $this->expectException(ValidationException::class);

        $this->source()->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '14:00',
            'closes_at' => '09:00',
        ]);
    }

    public function test_active_intervals_for_the_same_source_and_day_cannot_overlap(): void
    {
        $source = $this->source();

        $source->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '09:00',
            'closes_at' => '14:00',
        ]);

        $this->expectException(ValidationException::class);

        $source->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '13:30',
            'closes_at' => '16:00',
        ]);
    }

    public function test_the_same_interval_can_be_used_by_different_sources(): void
    {
        foreach ([$this->source('one'), $this->source('two')] as $source) {
            $source->openingHours()->create([
                'day_of_week' => 1,
                'opens_at' => '09:00',
                'closes_at' => '14:00',
            ]);
        }

        $this->assertSame(2, SourceOpeningHour::count());
    }

    private function source(string $suffix = 'main'): Source
    {
        return Source::create([
            'name' => "Web {$suffix}",
            'slug' => "web-{$suffix}",
            'api_token' => "token-{$suffix}",
            'is_active' => true,
        ]);
    }
}
