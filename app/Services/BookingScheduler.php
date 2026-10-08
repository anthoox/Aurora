<?php

namespace App\Services;

use App\Exceptions\BookingAlreadyExistsException;
use App\Exceptions\BookingSlotUnavailableException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Source;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingScheduler
{
    public function __construct(
        private readonly BookingAvailabilityService $availabilityService,
    ) {}

    public function createTimeSlotBooking(
        Source $source,
        Service $service,
        CarbonInterface $startsAt,
        array $customerData,
        ?string $customerMessage = null,
        string $availabilityPolicy = 'blocking',
    ): Booking {
        return $this->createRequest($source, $service, [
            ...$customerData,
            'booking_mode' => 'time_slots',
            'requested_date' => $startsAt->toDateString(),
            'booking_time' => $startsAt->format('H:i'),
            'customer_message' => $customerMessage,
            'availability_policy' => $availabilityPolicy,
        ]);
    }

    /** Create Connector requests under a shared service lock across sources. */
    public function createRequest(Source $source, Service $service, array $data): Booking
    {
        return DB::transaction(function () use ($source, $service, $data): Booking {
            // First read in the transaction: serialize even when no bookings exist yet.
            Service::query()->whereKey($service->getKey())->lockForUpdate()->firstOrFail();

            $lockedSource = Source::query()->whereKey($source->getKey())
                ->where('is_active', true)->lockForUpdate()->first();

            if (! $lockedSource) {
                throw ValidationException::withMessages(['source' => 'La fuente ya no está disponible.']);
            }

            if (! $lockedSource->services()->whereKey($service->getKey())
                ->wherePivot('is_active', true)->exists()) {
                throw ValidationException::withMessages(['service_id' => 'El servicio no está disponible para esta fuente.']);
            }

            $mode = $data['booking_mode'];
            $policy = $mode === 'time_slots' ? ($data['availability_policy'] ?? 'blocking') : 'blocking';
            if ($mode === 'time_slots') {
                app(BookingPolicyAuthorizer::class)->authorize($lockedSource, $policy);
            }

            $email = mb_strtolower(trim($data['email']), 'UTF-8');
            // Include equivalent historical spellings without rewriting existing customers.
            $customerIds = Customer::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->pluck('id');
            $startsAt = $mode === 'time_slots'
                ? CarbonImmutable::parse($data['requested_date'].' '.$data['booking_time'], config('app.timezone'))
                : null;

            if ($mode !== 'request_only') {
                $duplicates = Booking::query()->whereIn('customer_id', $customerIds)
                    ->where('service_id', $service->getKey())
                    ->where('booking_mode', $mode)->whereIn('status', ['pendiente', 'confirmada']);

                if ($mode === 'date_only') {
                    $duplicates->whereDate('requested_date', $data['requested_date']);
                } else {
                    // starts_at also covers historical time_slots without requested_date.
                    $duplicates->where('starts_at', $startsAt);
                }

                if ($duplicates->exists()) {
                    throw BookingAlreadyExistsException::create();
                }
            }

            $endsAt = null;
            if ($startsAt) {
                $availability = $this->availabilityService->availabilityFor($lockedSource, $service, $startsAt);
                if (! $availability['slots']->contains(fn (CarbonImmutable $slot): bool => $slot->equalTo($startsAt))) {
                    throw BookingSlotUnavailableException::create();
                }
                $endsAt = $startsAt->addMinutes($availability['duration_minutes']);
            }

            if ($customerIds->isNotEmpty()) {
                $customer = Customer::findOrFail($customerIds->first());
            } else {
                try {
                    $customer = Customer::firstOrCreate(['email' => $email], [
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'] ?? null,
                        'phone' => $data['phone'] ?? null,
                    ]);
                } catch (UniqueConstraintViolationException $exception) {
                    // Another service may create this email after our snapshot.
                    // A locking read sees the committed row under MySQL REPEATABLE READ.
                    $customer = Customer::query()->where('email', $email)->lockForUpdate()->first();
                    if (! $customer) {
                        throw $exception;
                    }
                }
            }

            return Booking::create([
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'source_id' => $lockedSource->id,
                'booking_mode' => $mode,
                'availability_policy' => $policy,
                'requested_date' => $mode === 'request_only' ? null : $data['requested_date'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'pendiente',
                'customer_message' => $data['customer_message'] ?? null,
            ]);
        }, 3);
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
