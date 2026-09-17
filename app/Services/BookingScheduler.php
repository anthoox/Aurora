<?php

namespace App\Services;

use App\Exceptions\BookingSlotUnavailableException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Source;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingScheduler
{
    public function __construct(
        private readonly BookingAvailabilityService $availabilityService,
    ) {}

    /**
     * @param  array{first_name: string, last_name?: string|null, email: string, phone?: string|null}  $customerData
     */
    public function createTimeSlotBooking(
        Source $source,
        Service $service,
        CarbonInterface $startsAt,
        array $customerData,
        ?string $customerMessage = null,
    ): Booking {
        return DB::transaction(function () use ($source, $service, $startsAt, $customerData, $customerMessage): Booking {
            $lockedSource = Source::query()
                ->whereKey($source->getKey())
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $lockedSource) {
                throw ValidationException::withMessages([
                    'source' => 'La fuente ya no está disponible.',
                ]);
            }

            $availability = $this->availabilityService->availabilityFor(
                $lockedSource,
                $service,
                $startsAt,
            );

            $slotIsAvailable = $availability['slots']->contains(
                fn (CarbonImmutable $slot): bool => $slot->equalTo($startsAt),
            );

            if (! $slotIsAvailable) {
                throw BookingSlotUnavailableException::create();
            }

            $customer = Customer::firstOrCreate(
                ['email' => $customerData['email']],
                [
                    'first_name' => $customerData['first_name'],
                    'last_name' => $customerData['last_name'] ?? null,
                    'phone' => $customerData['phone'] ?? null,
                ],
            );

            return Booking::create([
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'source_id' => $lockedSource->id,
                'booking_mode' => 'time_slots',
                'requested_date' => $startsAt->toDateString(),
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMinutes($availability['duration_minutes']),
                'status' => 'confirmada',
                'customer_message' => $customerMessage,
            ]);
        });
    }

    public function scheduleAndConfirm(
        Booking $booking,
        CarbonInterface $startsAt,
        int $durationMinutes,
    ): Booking {
        if (! $booking->canBeEdited()) {
            throw ValidationException::withMessages([
                'booking' => 'Esta reserva ya no se puede modificar.',
            ]);
        }

        if ($startsAt->isPast()) {
            throw ValidationException::withMessages([
                'start_time' => 'La fecha y hora deben ser posteriores al momento actual.',
            ]);
        }

        if ($durationMinutes <= 0) {
            throw ValidationException::withMessages([
                'duration_minutes' => 'La duración debe ser mayor que cero.',
            ]);
        }

        $booking->update([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($durationMinutes),
            'status' => 'confirmada',
        ]);

        return $booking->refresh();
    }
}
