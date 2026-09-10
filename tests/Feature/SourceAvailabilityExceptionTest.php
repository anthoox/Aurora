<?php

namespace Tests\Feature;

use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SourceAvailabilityExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_source_can_be_closed_for_a_specific_date(): void
    {
        $exception = $this->source()->availabilityExceptions()->create([
            'date' => now()->addDay()->toDateString(),
            'is_closed' => true,
        ]);

        $this->assertTrue($exception->is_closed);
        $this->assertNull($exception->opens_at);
        $this->assertNull($exception->closes_at);
    }

    public function test_a_date_can_have_split_special_opening_hours(): void
    {
        $source = $this->source();
        $date = now()->addDay()->toDateString();

        $source->availabilityExceptions()->createMany([
            [
                'date' => $date,
                'opens_at' => '10:00',
                'closes_at' => '13:00',
            ],
            [
                'date' => $date,
                'opens_at' => '16:00',
                'closes_at' => '19:00',
            ],
        ]);

        $this->assertCount(2, $source->availabilityExceptions);
    }

    public function test_a_closed_date_cannot_also_have_active_special_hours(): void
    {
        $source = $this->source();
        $date = now()->addDay()->toDateString();

        $source->availabilityExceptions()->create([
            'date' => $date,
            'is_closed' => true,
        ]);

        $this->expectException(ValidationException::class);

        $source->availabilityExceptions()->create([
            'date' => $date,
            'opens_at' => '10:00',
            'closes_at' => '13:00',
        ]);
    }

    public function test_active_special_intervals_for_the_same_date_cannot_overlap(): void
    {
        $source = $this->source();
        $date = now()->addDay()->toDateString();

        $source->availabilityExceptions()->create([
            'date' => $date,
            'opens_at' => '10:00',
            'closes_at' => '14:00',
        ]);

        $this->expectException(ValidationException::class);

        $source->availabilityExceptions()->create([
            'date' => $date,
            'opens_at' => '13:00',
            'closes_at' => '16:00',
        ]);
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
