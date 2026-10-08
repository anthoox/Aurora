<?php

namespace Tests\Feature;

use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Interaction;
use App\Models\Service;
use App\Models\Source;
use App\Models\User;
use App\Services\BookingScheduler;
use App\Services\GoogleCalendarService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ManualBookingSafetyTest extends TestCase
{
    use DatabaseMigrations;

    private Source $source;

    private Service $service;

    private BookingScheduler $scheduler;

    private $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = Source::create(['name' => 'Web', 'slug' => 'web', 'api_token' => 'token', 'is_active' => true]);
        $this->service = Service::create(['name' => 'Consulta']);
        $this->source->services()->attach($this->service, ['is_active' => true, 'duration_minutes' => 45]);
        $this->scheduler = app(BookingScheduler::class);
        $this->calendar = Mockery::mock(GoogleCalendarService::class);
        $this->calendar->shouldReceive('createEventFromBooking')->byDefault()->andReturn('event-test');
        $this->calendar->shouldReceive('updateEventFromBooking')->byDefault()->andReturnNull();
        $this->calendar->shouldReceive('deleteEvent')->byDefault()->andReturnNull();
        $this->app->instance(GoogleCalendarService::class, $this->calendar);
    }

    protected function tearDown(): void
    {
        // The historical down() makes starts_at NOT NULL; clear only test fixtures first.
        DB::table('bookings')->delete();
        parent::tearDown();
    }

    private function request(array $overrides = []): Booking
    {
        $customer = Customer::create(['first_name' => 'Cliente', 'email' => uniqid().'@example.test']);

        return $this->scheduler->createManual(array_replace([
            'customer_id' => $customer->id, 'source_id' => $this->source->id,
            'service_id' => $this->service->id, 'booking_mode' => 'time_slots',
            'availability_policy' => 'non_blocking', 'requested_date' => now()->addDays(2)->toDateString(),
            'starts_at' => now()->addDays(2)->startOfDay()->setTime(10, 0),
            'ends_at' => now()->addDays(2)->startOfDay()->setTime(10, 45),
        ], $overrides))->fresh();
    }

    private function rejects(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected validation failure');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
    }

    public function test_confirm_preserves_exact_schedule_and_excludes_itself(): void
    {
        foreach ([30, 45, 90] as $minutes) {
            $start = now()->addDays($minutes)->setTime(10, 0);
            $booking = $this->request(['starts_at' => $start, 'ends_at' => $start->copy()->addMinutes($minutes), 'availability_policy' => 'blocking']);
            $confirmed = $this->scheduler->confirm($booking)->fresh();
            $this->assertSame('confirmada', $confirmed->status);
            $this->assertSame($minutes, (int) $confirmed->starts_at->diffInMinutes($confirmed->ends_at));
            $this->assertNotNull($confirmed->google_event_id);
        }
    }

    public function test_conflict_keeps_second_pending_and_history_unchanged(): void
    {
        $a = $this->request();
        $b = $this->request();
        $this->scheduler->confirm($a);
        $before = $b->fresh()->getAttributes();
        $events = $b->events()->count();
        $this->rejects(fn () => $this->scheduler->confirm($b));
        $this->assertSame($before, $b->fresh()->getAttributes());
        $this->assertSame($events, $b->events()->count());
    }

    public function test_pending_blocking_partial_overlap_and_contiguous_interval(): void
    {
        $a = $this->request(['availability_policy' => 'blocking']);
        $this->rejects(fn () => $this->request([
            'starts_at' => $a->starts_at->copy()->addMinutes(30), 'ends_at' => $a->ends_at->copy()->addMinutes(30),
        ]));
        $b = $this->request(['starts_at' => $a->ends_at, 'ends_at' => $a->ends_at->copy()->addMinutes(30)]);
        $this->assertSame('confirmada', $this->scheduler->confirm($b)->status);
    }

    public function test_reprogramming_and_service_change_rollback_on_conflict(): void
    {
        $a = $this->scheduler->confirm($this->request())->fresh();
        $b = $this->request(['starts_at' => $a->ends_at, 'ends_at' => $a->ends_at->copy()->addMinutes(45)]);
        $before = $b->getAttributes();
        $this->rejects(fn () => $this->scheduler->updateManual($b, ['starts_at' => $a->starts_at, 'ends_at' => $a->ends_at, 'status' => 'confirmada']));
        $this->assertSame($before, $b->fresh()->getAttributes());
        $other = Service::create(['name' => 'Otro']);
        $this->source->services()->attach($other, ['is_active' => true]);
        $c = $this->request(['service_id' => $other->id]);
        $this->rejects(fn () => $this->scheduler->updateManual($c, ['service_id' => $this->service->id]));
        $this->assertSame($other->id, $c->fresh()->service_id);
    }

    public function test_date_only_preserves_requested_date_and_can_edit_without_schedule(): void
    {
        $booking = $this->request(['booking_mode' => 'date_only', 'starts_at' => null, 'ends_at' => null]);
        $date = $booking->requested_date->toDateString();
        $this->scheduler->updateManual($booking, ['notes' => 'Contactar']);
        $booking = $booking->fresh();
        $this->assertNull($booking->starts_at);
        $confirmed = $this->scheduler->scheduleAndConfirm($booking, now()->addDays(5)->setTime(14, 0), 90);
        $this->assertSame($date, $confirmed->requested_date->toDateString());
        $this->assertSame(90, (int) $confirmed->starts_at->diffInMinutes($confirmed->ends_at));
    }

    public function test_edit_cannot_introduce_duplicate_or_overwrite_stale_confirmation(): void
    {
        $a = $this->request(['booking_mode' => 'date_only', 'starts_at' => null, 'ends_at' => null]);
        $b = $this->request(['booking_mode' => 'date_only', 'starts_at' => null, 'ends_at' => null]);
        $this->rejects(fn () => $this->scheduler->updateManual($b, ['customer_id' => $a->customer_id]));
        $slot = $this->request();
        $this->scheduler->confirm($slot);
        $this->rejects(fn () => $this->scheduler->updateManual($slot, ['status' => 'pendiente']));
        $this->assertSame('confirmada', $slot->fresh()->status);
    }

    public function test_calendar_waits_for_outer_commit_and_does_not_run_on_rollback(): void
    {
        $a = $this->request();
        $this->calendar->shouldReceive('createEventFromBooking')->once()->withArgs(function ($booking) {
            $this->assertSame('confirmada', Booking::find($booking->id)->status);

            return true;
        })->andReturn('committed-event');
        DB::beginTransaction();
        $this->scheduler->confirm($a);
        $this->assertNull($a->fresh()->google_event_id);
        DB::rollBack();
        $this->assertSame('pendiente', $a->fresh()->status);
        DB::beginTransaction();
        $this->scheduler->confirm($a->fresh());
        $this->assertNull($a->fresh()->google_event_id);
        DB::commit();
        $this->assertSame('committed-event', $a->fresh()->google_event_id);
        $this->assertSame(2, $a->events()->count());
    }

    public function test_calendar_update_cancel_and_deletion_guard(): void
    {
        $booking = $this->scheduler->confirm($this->request())->fresh();
        $this->calendar->shouldReceive('updateEventFromBooking')->once();
        $this->calendar->shouldReceive('deleteEvent')->once()->with('event-test');
        $this->rejects(fn () => $this->scheduler->delete($booking));
        $this->rejects(fn () => $booking->delete());
        $booking = $this->scheduler->updateManual($booking, ['notes' => 'Actualizada'])->fresh();
        $this->scheduler->updateManual($booking, ['status' => 'cancelada']);
        $this->assertNull($booking->fresh()->google_event_id);
        $this->rejects(fn () => $this->scheduler->updateManual($booking->fresh(), ['status' => 'confirmada']));
    }

    public function test_historical_null_service_is_preserved_but_cannot_be_confirmed(): void
    {
        $booking = $this->request();
        Booking::withoutEvents(fn () => $booking->update(['service_id' => null]));
        $booking = $booking->fresh();
        $this->scheduler->updateManual($booking, ['notes' => 'Histórica']);
        $this->rejects(fn () => $this->scheduler->confirm($booking->fresh()));
        $this->assertNull($booking->fresh()->service_id);
    }

    public function test_lead_and_booking_changes_are_atomic(): void
    {
        $customer = Customer::create(['first_name' => 'Lead', 'email' => 'lead@example.test']);
        $lead = Interaction::create(['customer_id' => $customer->id, 'source_id' => $this->source->id, 'service_id' => $this->service->id, 'status' => 'nuevo']);
        $blocker = $this->request(['availability_policy' => 'blocking']);
        $data = ['starts_at' => $blocker->starts_at, 'ends_at' => $blocker->ends_at, 'status' => 'confirmada'];
        $this->rejects(fn () => $this->scheduler->createFromInteraction($lead, $data));
        $this->assertSame('nuevo', $lead->fresh()->status);
        $this->assertSame(0, $lead->bookings()->count());
        $data['starts_at'] = $blocker->ends_at;
        $data['ends_at'] = $blocker->ends_at->copy()->addMinutes(45);
        $this->scheduler->createFromInteraction($lead, $data);
        $this->assertSame('reservado', $lead->fresh()->status);
        $this->assertSame(1, $lead->bookings()->count());
    }

    public function test_form_translation_keeps_minutes_and_original_requested_date(): void
    {
        $booking = $this->request();
        $data = $this->scheduler->formData(['booking_date' => now()->addDays(5)->toDateString(), 'start_time' => '12:15', 'duration_minutes' => 45], $booking);
        $this->assertArrayNotHasKey('requested_date', $data);
        $this->assertSame(45, (int) $data['starts_at']->diffInMinutes($data['ends_at']));
    }

    private function employee(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_filament_confirm_action_and_conflict_notification(): void
    {
        $this->employee();
        $a = $this->request();
        $b = $this->request();
        Livewire::test(ViewBooking::class, ['record' => $a->id])
            ->callAction('confirmRequest')->assertHasNoActionErrors();
        $this->assertSame('confirmada', $a->fresh()->status);
        Livewire::test(ViewBooking::class, ['record' => $b->id])
            ->callAction('confirmRequest')->assertNotified('No se pudo guardar la reserva');
        $this->assertSame('pendiente', $b->fresh()->status);
    }

    public function test_filament_edit_without_schedule_and_exact_duration(): void
    {
        $this->employee();
        $a = $this->request(['booking_mode' => 'date_only', 'starts_at' => null, 'ends_at' => null]);
        Livewire::test(EditBooking::class, ['record' => $a->id])
            ->fillForm(['notes' => 'Sin horario'])->call('save')->assertHasNoFormErrors();
        $this->assertNull($a->fresh()->starts_at);
        $b = $this->request();
        Livewire::test(EditBooking::class, ['record' => $b->id])
            ->fillForm(['notes' => '45 minutos'])->call('save')->assertHasNoFormErrors();
        $this->assertSame(45, (int) $b->fresh()->starts_at->diffInMinutes($b->fresh()->ends_at));
    }

    public function test_filament_state_selector_cannot_confirm_an_overlap(): void
    {
        $this->employee();
        $this->scheduler->confirm($this->request());
        // Seed an existing non_blocking request; current creation correctly rejects the confirmed slot.
        $b = Booking::withoutEvents(fn () => Booking::create([
            'customer_id' => Customer::create(['first_name' => 'B', 'email' => 'b@example.test'])->id,
            'source_id' => $this->source->id, 'service_id' => $this->service->id,
            'booking_mode' => 'time_slots', 'availability_policy' => 'non_blocking', 'status' => 'pendiente',
            'starts_at' => now()->addDays(2)->setTime(10, 0), 'ends_at' => now()->addDays(2)->setTime(10, 45),
        ]));
        Livewire::test(EditBooking::class, ['record' => $b->id])
            ->fillForm(['status' => 'confirmada'])->call('save')->assertNotified('No se pudo guardar la reserva');
        $this->assertSame('pendiente', $b->fresh()->status);
    }

    public function test_calendar_failure_does_not_rollback_committed_booking(): void
    {
        $this->calendar->shouldReceive('createEventFromBooking')->once()->andThrow(new \RuntimeException('Calendar unavailable'));
        $booking = $this->scheduler->confirm($this->request())->fresh();
        $this->assertSame('confirmada', $booking->status);
        $this->assertNull($booking->google_event_id);
        $this->assertSame(2, $booking->events()->count());
    }

    public function test_confirmed_reprogramming_and_service_change_preserve_requested_date(): void
    {
        $booking = $this->scheduler->confirm($this->request())->fresh();
        $requested = $booking->requested_date->toDateString();
        $other = Service::create(['name' => 'Otro']);
        $this->source->services()->attach($other, ['is_active' => true]);
        $this->calendar->shouldReceive('updateEventFromBooking')->once();
        $start = now()->addDays(6)->setTime(12, 15);
        $updated = $this->scheduler->updateManual($booking, [
            'service_id' => $other->id, 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(90),
        ])->fresh();
        $this->assertSame($other->id, $updated->service_id);
        $this->assertSame($requested, $updated->requested_date->toDateString());
        $this->assertSame(90, (int) $updated->starts_at->diffInMinutes($updated->ends_at));
        $this->assertSame(1, $updated->events()->where('type', 'service_id_changed')->count());
        $this->assertSame(1, $updated->events()->where('type', 'date_changed')->count());
    }

    public function test_request_only_requires_complete_schedule_and_terminal_bookings_are_locked(): void
    {
        $booking = $this->request(['booking_mode' => 'request_only', 'requested_date' => null, 'starts_at' => null, 'ends_at' => null]);
        $this->rejects(fn () => $this->scheduler->confirm($booking));
        $this->assertSame('pendiente', $booking->fresh()->status);
        $confirmed = $this->scheduler->scheduleAndConfirm($booking, now()->addDays(5)->setTime(14, 0), 30)->fresh();
        $this->assertNull($confirmed->requested_date);
        $this->scheduler->updateManual($confirmed, ['status' => 'realizada']);
        $this->rejects(fn () => $this->scheduler->updateManual($confirmed->fresh(), ['notes' => 'No permitido']));
    }

    public function test_cross_source_confirmations_respect_same_service_and_other_services_are_independent(): void
    {
        $otherSource = Source::create(['name' => 'Otra web', 'slug' => 'other', 'api_token' => 'other', 'is_active' => true]);
        $otherSource->services()->attach($this->service, ['is_active' => true]);
        $a = $this->request();
        $b = $this->request(['source_id' => $otherSource->id]);
        $this->scheduler->confirm($a);
        $this->rejects(fn () => $this->scheduler->confirm($b));
        $otherService = Service::create(['name' => 'Independiente']);
        $otherSource->services()->attach($otherService, ['is_active' => true]);
        $this->assertSame('confirmada', $this->scheduler->updateManual($b->fresh(), [
            'service_id' => $otherService->id, 'status' => 'confirmada',
        ])->status);
    }

    public function test_missing_calendar_credentials_throw_controlled_exception(): void
    {
        config(['services.google_calendar.credentials_path' => 'tests/nonexistent-calendar-credentials.json']);
        $this->expectException(\RuntimeException::class);
        new GoogleCalendarService;
    }

    public function test_filament_manual_creation_cannot_bypass_conflicts(): void
    {
        $this->employee();
        $a = $this->scheduler->confirm($this->request())->fresh();
        $customer = Customer::create(['first_name' => 'Nuevo', 'email' => 'manual@example.test']);
        Livewire::test(CreateBooking::class)
            ->fillForm([
                'customer_id' => $customer->id, 'source_id' => $this->source->id,
                'service_id' => $this->service->id, 'booking_date' => $a->starts_at->toDateString(),
                'start_time' => '10:00', 'duration_minutes' => 30, 'status' => 'confirmada',
            ])->call('create')->assertNotified('No se pudo guardar la reserva');
        $this->assertSame(1, Booking::count());
    }

    public function test_outer_rollback_restores_lead_booking_and_both_histories(): void
    {
        $customer = Customer::create(['first_name' => 'Lead', 'email' => 'atomic@example.test']);
        $lead = Interaction::create(['customer_id' => $customer->id, 'source_id' => $this->source->id, 'service_id' => $this->service->id, 'status' => 'nuevo']);
        $this->calendar->shouldNotReceive('createEventFromBooking');
        DB::beginTransaction();
        $this->scheduler->createFromInteraction($lead, [
            'starts_at' => now()->addDays(4)->setTime(10, 0),
            'ends_at' => now()->addDays(4)->setTime(10, 45), 'status' => 'confirmada',
        ]);
        $this->assertSame('reservado', $lead->fresh()->status);
        DB::rollBack();
        $this->assertSame('nuevo', $lead->fresh()->status);
        $this->assertSame(0, Booking::count());
        $this->assertSame(0, $lead->events()->count());
        $this->assertSame(0, DB::table('booking_events')->count());
    }
}
