<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Services\SourceOpeningHoursConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SourceOpeningHoursConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_each_range_for_every_selected_day(): void
    {
        $source = $this->source();

        $this->configurator()->configure(
            $source,
            [1, 2, 3, 4, 5],
            [
                ['opens_at' => '09:00', 'closes_at' => '14:00'],
                ['opens_at' => '18:00', 'closes_at' => '22:00'],
            ],
            60,
            'add',
        );

        $this->assertSame(60, $source->refresh()->slot_interval_minutes);
        $this->assertSame(10, $source->openingHours()->count());
        $this->assertSame(2, $source->openingHours()->where('day_of_week', 3)->count());
        $this->assertSame(0, $source->openingHours()->where('day_of_week', 6)->count());
    }

    public function test_add_mode_rolls_back_every_change_when_a_range_overlaps(): void
    {
        $source = $this->source();
        $source->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '09:00',
            'closes_at' => '14:00',
        ]);

        try {
            $this->configurator()->configure(
                $source,
                [1],
                [['opens_at' => '13:00', 'closes_at' => '18:00']],
                60,
                'add',
            );

            $this->fail('Expected overlapping ranges to be rejected.');
        } catch (ValidationException) {
            $this->assertSame(30, $source->refresh()->slot_interval_minutes);
            $this->assertSame(1, $source->openingHours()->count());
        }
    }

    public function test_replace_mode_only_replaces_the_selected_days(): void
    {
        $source = $this->source();
        $source->openingHours()->createMany([
            [
                'day_of_week' => 1,
                'opens_at' => '09:00',
                'closes_at' => '14:00',
            ],
            [
                'day_of_week' => 1,
                'opens_at' => '18:00',
                'closes_at' => '20:00',
                'is_active' => false,
            ],
            [
                'day_of_week' => 2,
                'opens_at' => '10:00',
                'closes_at' => '16:00',
            ],
        ]);

        $this->configurator()->configure(
            $source,
            [1],
            [['opens_at' => '11:00', 'closes_at' => '15:00']],
            45,
            'replace',
        );

        $this->assertSame(45, $source->refresh()->slot_interval_minutes);
        $this->assertSame([
            ['day_of_week' => 1, 'opens_at' => '11:00', 'closes_at' => '15:00'],
            ['day_of_week' => 2, 'opens_at' => '10:00', 'closes_at' => '16:00'],
        ], $source->openingHours()
            ->orderBy('day_of_week')
            ->get(['day_of_week', 'opens_at', 'closes_at'])
            ->toArray());
    }

    public function test_it_rejects_a_configuration_without_days(): void
    {
        $this->expectException(ValidationException::class);

        $this->configurator()->configure(
            $this->source(),
            [],
            [['opens_at' => '09:00', 'closes_at' => '14:00']],
            30,
            'add',
        );
    }

    public function test_it_rejects_a_configuration_without_ranges(): void
    {
        $this->expectException(ValidationException::class);

        $this->configurator()->configure(
            $this->source(),
            [1],
            [],
            30,
            'add',
        );
    }

    public function test_it_rejects_overlapping_ranges_before_writing(): void
    {
        $source = $this->source();

        try {
            $this->configurator()->configure(
                $source,
                [1, 2],
                [
                    ['opens_at' => '09:00', 'closes_at' => '14:00'],
                    ['opens_at' => '13:00', 'closes_at' => '18:00'],
                ],
                30,
                'add',
            );

            $this->fail('Expected overlapping ranges to be rejected.');
        } catch (ValidationException) {
            $this->assertSame(0, $source->openingHours()->count());
        }
    }

    private function configurator(): SourceOpeningHoursConfigurator
    {
        return app(SourceOpeningHoursConfigurator::class);
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
