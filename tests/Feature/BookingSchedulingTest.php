<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Services\BookingScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_date_only_request_can_be_scheduled_and_confirmed(): void
    {
        $booking = $this->dateOnlyBooking();
        $startsAt = now()->addDay()->setTime(17, 30);

        app(BookingScheduler::class)->scheduleAndConfirm($booking, $startsAt, 30);

        $booking->refresh();

        $this->assertSame('date_only', $booking->booking_mode);
        $this->assertSame('confirmada', $booking->status);
        $this->assertTrue($booking->starts_at->equalTo($startsAt));
        $this->assertTrue($booking->ends_at->equalTo($startsAt->copy()->addMinutes(30)));
        $this->assertSame(now()->addDay()->toDateString(), $booking->requested_date->toDateString());
    }

    public function test_a_booking_cannot_be_confirmed_without_a_complete_schedule(): void
    {
        $booking = $this->dateOnlyBooking();

        $this->expectException(ValidationException::class);

        $booking->update(['status' => 'confirmada']);
    }

    public function test_the_end_must_be_after_the_start(): void
    {
        $booking = $this->dateOnlyBooking();
        $startsAt = now()->addDay();

        $this->expectException(ValidationException::class);

        $booking->update([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt,
        ]);
    }

    private function dateOnlyBooking(): Booking
    {
        $customer = Customer::create([
            'first_name' => 'Ana',
            'email' => 'ana@example.com',
        ]);

        return Booking::create([
            'customer_id' => $customer->id,
            'booking_mode' => 'date_only',
            'requested_date' => now()->addDay()->toDateString(),
            'status' => 'pendiente',
        ]);
    }
}
