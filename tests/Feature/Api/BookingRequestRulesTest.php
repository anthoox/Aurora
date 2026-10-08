<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Source;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRequestRulesTest extends TestCase
{
    use RefreshDatabase;

    private Source $source;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-10 08:00');
        CarbonImmutable::setTestNow('2026-09-10 08:00');
        $this->service = Service::create(['name' => 'Consulta']);
        $this->source = $this->source('main');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function source(string $slug): Source
    {
        $source = Source::create(['name' => $slug, 'slug' => $slug, 'api_token' => $slug, 'is_active' => true]);
        $source->services()->attach($this->service, ['is_active' => true, 'duration_minutes' => 30]);
        $source->openingHours()->create(['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '12:00']);

        return $source;
    }

    private function submit(array $overrides = [], ?Source $source = null)
    {
        return $this->withHeader('X-Aurora-Token', ($source ?? $this->source)->api_token)
            ->postJson('/api/bookings', array_replace([
                'first_name' => 'Ana', 'email' => 'ana@example.com',
                'service_id' => $this->service->id, 'booking_mode' => 'time_slots',
                'requested_date' => '2026-09-14', 'booking_time' => '09:00',
            ], $overrides));
    }

    public function test_duplicate_has_priority_over_unavailable_slot_and_normalizes_email(): void
    {
        $this->submit(['email' => '  ANA@EXAMPLE.COM  '])->assertCreated()->assertJsonPath('data.status', 'pendiente');
        $this->submit()->assertUnprocessable()->assertJsonPath('code', 'booking_already_exists');
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', ['email' => 'ana@example.com']);
        $this->assertDatabaseHas('bookings', ['availability_policy' => 'blocking']);
    }

    public function test_date_only_duplicates_are_shared_between_sources_but_other_customers_are_allowed(): void
    {
        $other = $this->source('other');
        $this->submit(['booking_mode' => 'date_only', 'booking_time' => 'ignored'])->assertCreated();
        $this->submit(['booking_mode' => 'date_only'], $other)->assertUnprocessable()->assertJsonPath('code', 'booking_already_exists');
        $this->submit(['booking_mode' => 'date_only', 'email' => 'other@example.com'])->assertCreated();
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_modes_and_different_hours_do_not_count_as_duplicates(): void
    {
        $this->submit(['booking_mode' => 'date_only'])->assertCreated();
        $this->submit()->assertCreated();
        $this->submit(['booking_time' => '09:30'])->assertCreated();
        $this->submit(['booking_mode' => 'request_only'])->assertCreated()->assertJsonPath('data.requested_date', null);
        $this->submit(['booking_mode' => 'request_only'])->assertCreated();
    }

    public function test_cancelled_requests_can_be_repeated_but_confirmed_requests_are_duplicates(): void
    {
        $this->submit()->assertCreated();
        Booking::first()->update(['status' => 'cancelada']);
        $this->submit()->assertCreated();
        Booking::withoutEvents(fn () => Booking::latest('id')->first()->update(['status' => 'confirmada', 'requested_date' => null]));
        $this->submit()->assertUnprocessable()->assertJsonPath('code', 'booking_already_exists');
    }

    public function test_non_blocking_requires_source_authorization_and_does_not_hide_slots(): void
    {
        $this->submit(['availability_policy' => 'non_blocking'])->assertUnprocessable()->assertJsonValidationErrors('availability_policy');
        $this->assertDatabaseCount('bookings', 0);
        config(['bookings.source_availability_policies' => [$this->source->id => ['blocking', 'non_blocking']]]);
        $this->submit(['availability_policy' => 'non_blocking'])->assertCreated();
        $this->submit(['availability_policy' => 'non_blocking', 'email' => 'other@example.com'])->assertCreated();
        $this->submit(['availability_policy' => 'non_blocking'])->assertUnprocessable()->assertJsonPath('code', 'booking_already_exists');
        $this->withHeader('X-Aurora-Token', 'main')->getJson('/api/availability?service_id='.$this->service->id.'&date=2026-09-14')
            ->assertOk()->assertJsonPath('data.slots.0', '09:00');
        $this->submit(['email' => 'blocking@example.com', 'availability_policy' => 'blocking'])->assertCreated();
        $this->submit(['email' => 'third@example.com', 'availability_policy' => 'non_blocking'])
            ->assertUnprocessable()->assertJsonPath('code', 'booking_slot_unavailable');
    }

    public function test_service_blocking_is_shared_between_sources_and_isolated_from_other_services(): void
    {
        $other = $this->source('other');
        $this->submit()->assertCreated();
        $this->submit([], $other)->assertUnprocessable()->assertJsonPath('code', 'booking_already_exists');
        $this->submit(['email' => 'other@example.com'], $other)->assertUnprocessable()->assertJsonPath('code', 'booking_slot_unavailable');
        $second = Service::create(['name' => 'Otro']);
        $this->source->services()->attach($second, ['is_active' => true, 'duration_minutes' => 30]);
        $this->submit(['service_id' => $second->id])->assertCreated();
    }

    public function test_confirmed_date_only_blocks_only_its_interval_and_null_services_are_ignored(): void
    {
        $this->submit(['booking_mode' => 'date_only'])->assertCreated();
        Booking::withoutEvents(fn () => Booking::first()->update([
            'status' => 'confirmada', 'starts_at' => '2026-09-14 09:00', 'ends_at' => '2026-09-14 09:30',
        ]));
        $this->submit(['email' => 'other@example.com'])->assertUnprocessable()->assertJsonPath('code', 'booking_slot_unavailable');
        $this->submit(['booking_mode' => 'date_only', 'email' => 'other@example.com'])->assertCreated();
        $this->submit(['booking_time' => '09:30'])->assertCreated();
        Booking::withoutEvents(fn () => Booking::where('status', 'confirmada')->first()->update(['service_id' => null]));
        $this->submit(['email' => 'third@example.com'])->assertCreated();
        $this->assertDatabaseHas('bookings', ['service_id' => null, 'status' => 'confirmada']);
    }

    public function test_equivalent_existing_customer_is_reused_without_rewriting_history(): void
    {
        $customer = Customer::create(['first_name' => 'Anterior', 'email' => ' ANA@EXAMPLE.COM ']);
        $this->submit(['booking_mode' => 'date_only'])->assertCreated();
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('bookings', ['customer_id' => $customer->id]);
        $this->assertSame(' ANA@EXAMPLE.COM ', $customer->fresh()->email);
    }

    public function test_date_only_duplicate_states_and_different_dates(): void
    {
        $this->submit(['booking_mode' => 'date_only'])->assertCreated();
        Booking::first()->update(['status' => 'cancelada']);
        $this->submit(['booking_mode' => 'date_only'])->assertCreated();
        Booking::withoutEvents(fn () => Booking::latest('id')->first()->update([
            'status' => 'confirmada', 'starts_at' => '2026-09-14 10:00', 'ends_at' => '2026-09-14 10:30',
        ]));
        $this->submit(['booking_mode' => 'date_only'])->assertUnprocessable()->assertJsonPath('code', 'booking_already_exists');
        $this->submit(['booking_mode' => 'date_only', 'requested_date' => '2026-09-15'])->assertCreated();
    }

    public function test_realizada_does_not_block_or_count_as_duplicate(): void
    {
        $this->submit()->assertCreated();
        Booking::withoutEvents(fn () => Booking::first()->update(['status' => 'realizada']));
        $this->submit()->assertCreated();
    }

    public function test_confirmed_non_blocking_request_still_blocks_for_other_customers(): void
    {
        config(['bookings.source_availability_policies' => [$this->source->id => ['blocking', 'non_blocking']]]);
        $this->submit(['availability_policy' => 'non_blocking'])->assertCreated();
        Booking::withoutEvents(fn () => Booking::first()->update(['status' => 'confirmada']));
        $this->submit(['email' => 'other@example.com', 'availability_policy' => 'non_blocking'])
            ->assertUnprocessable()->assertJsonPath('code', 'booking_slot_unavailable');
    }

    public function test_policy_authorization_is_per_source_and_invalid_policy_is_rejected(): void
    {
        $other = $this->source('other');
        config(['bookings.source_availability_policies' => [$this->source->id => ['blocking', 'non_blocking']]]);
        $this->submit(['availability_policy' => 'non_blocking'], $other)->assertUnprocessable()->assertJsonValidationErrors('availability_policy');
        $this->submit(['availability_policy' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('availability_policy');
    }
}
