<?php

namespace App\Services;

use App\Exceptions\BookingAlreadyExistsException;
use App\Exceptions\BookingSlotUnavailableException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Interaction;
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
                throw ValidationException::withMessages(['source' => 'La fuente ya no estÃ¡ disponible.']);
            }

            if (! $lockedSource->services()->whereKey($service->getKey())
                ->wherePivot('is_active', true)->exists()) {
                throw ValidationException::withMessages(['service_id' => 'El servicio no estÃ¡ disponible para esta fuente.']);
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

    public function scheduleAndConfirm(Booking $booking, CarbonInterface $startsAt, int $durationMinutes): Booking
    {
        if ($durationMinutes <= 0) {
            throw ValidationException::withMessages(['duration_minutes' => 'La duración debe ser mayor que cero.']);
        }

        return $this->updateManual($booking, [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($durationMinutes),
            'status' => 'confirmada',
        ]);
    }

    public function confirm(Booking $booking): Booking
    {
        return $this->updateManual($booking, ['status' => 'confirmada']);
    }

    /** Translate form fields without changing the originally requested date. */
    public function formData(array $data, ?Booking $booking = null): array
    {
        if (! empty($data['start_time'])) {
            if (empty($data['booking_date']) || empty($data['duration_minutes'])) {
                throw ValidationException::withMessages(['booking' => 'Indica fecha y duración para el horario.']);
            }
            $data['starts_at'] = CarbonImmutable::parse($data['booking_date'].' '.$data['start_time'], config('app.timezone'));
            $data['ends_at'] = $data['starts_at']->addMinutes((int) $data['duration_minutes']);
        } else {
            $data['starts_at'] = null;
            $data['ends_at'] = null;
        }
        if (! $booking) {
            $data['requested_date'] = $data['booking_date'] ?? null;
        } else {
            unset($data['requested_date']);
        }
        unset($data['booking_date'], $data['start_time'], $data['duration_minutes']);

        return $data;
    }

    public function createManual(array $data): Booking
    {
        return DB::transaction(function () use ($data): Booking {
            $this->lockServices([$data['service_id'] ?? null]);
            $booking = new Booking(array_replace([
                'booking_mode' => 'time_slots', 'availability_policy' => 'blocking', 'status' => 'pendiente',
            ], $data));
            $this->validateManual($booking, true);
            $booking->save();

            return $booking;
        }, 3);
    }

    public function updateManual(Booking $booking, array $data): Booking
    {
        return DB::transaction(function () use ($booking, $data): Booking {
            $locked = $this->lockBooking($booking, $data['service_id'] ?? $booking->service_id);
            if (! $locked->canBeEdited()) {
                throw ValidationException::withMessages(['booking' => 'Esta reserva ya no se puede modificar.']);
            }
            // A stale form must not overwrite a concurrent confirmation or reprogramming.
            foreach (['status', 'service_id', 'source_id', 'customer_id', 'starts_at', 'ends_at', 'booking_mode', 'availability_policy'] as $field) {
                $original = $booking->getRawOriginal($field);
                if ($field === 'availability_policy') {
                    $original ??= 'blocking';
                }
                if ((string) $locked->getRawOriginal($field) !== (string) $original) {
                    throw ValidationException::withMessages(['booking' => 'La reserva ha cambiado. Recarga la página antes de continuar.']);
                }
            }
            unset($data['requested_date'], $data['google_event_id'], $data['google_synced_at']);
            $locked->fill($data);
            $schedulingChanged = $locked->isDirty(['status', 'service_id', 'source_id', 'starts_at', 'ends_at', 'customer_id', 'booking_mode', 'availability_policy']);
            $this->validateManual($locked, $schedulingChanged);
            $locked->save();

            return $locked;
        }, 3);
    }

    private function lockServices(array $ids): void
    {
        $ids = array_values(array_unique(array_filter($ids)));
        sort($ids, SORT_NUMERIC);
        foreach ($ids as $id) {
            if (! Service::query()->whereKey($id)->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['service_id' => 'Selecciona un servicio válido.']);
            }
        }
    }

    private function lockBooking(Booking $booking, ?int $targetServiceId = null): Booking
    {
        $this->lockServices([$booking->service_id, $targetServiceId]);
        $locked = Booking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
        if ($locked->service_id !== $booking->service_id) {
            throw ValidationException::withMessages(['booking' => 'El servicio ha cambiado. Recarga la página.']);
        }

        return $locked;
    }

    private function validateManual(Booking $booking, bool $schedulingChanged): void
    {
        if (! in_array($booking->status, ['pendiente', 'confirmada', 'cancelada', 'realizada'], true)) {
            throw ValidationException::withMessages(['status' => 'Estado no válido.']);
        }
        if (! in_array($booking->booking_mode, ['request_only', 'date_only', 'time_slots'], true)
            || ! in_array($booking->availability_policy, ['blocking', 'non_blocking'], true)) {
            throw ValidationException::withMessages(['booking' => 'Modo o política no válidos.']);
        }
        if (($booking->starts_at === null) !== ($booking->ends_at === null)) {
            throw ValidationException::withMessages(['booking' => 'Indica inicio y fin del horario.']);
        }
        $active = in_array($booking->status, ['pendiente', 'confirmada'], true);
        if (! $schedulingChanged || ! $active) {
            return;
        }
        if (! $booking->service_id || ! $booking->source_id
            || ! Source::query()->whereKey($booking->source_id)->where('is_active', true)
                ->whereHas('services', fn ($query) => $query->whereKey($booking->service_id)->where('service_source.is_active', true))->exists()) {
            throw ValidationException::withMessages(['service_id' => 'Selecciona un servicio activo para el origen.']);
        }
        if ($booking->starts_at && $booking->starts_at->isPast()) {
            throw ValidationException::withMessages(['start_time' => 'El horario debe ser posterior al momento actual.']);
        }
        $customer = Customer::findOrFail($booking->customer_id);
        $duplicates = Booking::query()->where('service_id', $booking->service_id)
            ->where('booking_mode', $booking->booking_mode)->whereIn('status', ['pendiente', 'confirmada'])
            ->whereIn('customer_id', Customer::query()->select('id')->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($customer->email), 'UTF-8')]))
            ->when($booking->exists, fn ($query) => $query->whereKeyNot($booking->getKey()));
        if ($booking->booking_mode === 'date_only' && $booking->requested_date) {
            $duplicates->whereDate('requested_date', $booking->requested_date->toDateString());
        } elseif ($booking->booking_mode === 'time_slots' && $booking->starts_at) {
            $duplicates->where('starts_at', $booking->starts_at);
        } else {
            $duplicates = null;
        }
        if ($duplicates?->lockForUpdate()->first()) {
            throw BookingAlreadyExistsException::create();
        }
        if ($booking->starts_at) {
            $this->availabilityService->assertNoConflict($booking);
        }
    }

    public function createFromInteraction(Interaction $interaction, array $data): Booking
    {
        return DB::transaction(function () use ($interaction, $data): Booking {
            $this->lockServices([$interaction->service_id]);
            $lead = Interaction::query()->whereKey($interaction->id)->lockForUpdate()->firstOrFail();
            if ($lead->service_id !== $interaction->service_id || ! $lead->canBeEdited()
                || $lead->bookings()->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['booking' => 'El lead ha cambiado o ya tiene una reserva. Recarga la página.']);
            }
            $booking = $this->createManual(array_replace($data, [
                'customer_id' => $lead->customer_id, 'interaction_id' => $lead->id,
                'source_id' => $lead->source_id, 'service_id' => $lead->service_id,
            ]));
            if (in_array($lead->status, ['nuevo', 'contactado'], true)) {
                $lead->update(['status' => 'reservado']);
            }

            return $booking;
        }, 3);
    }

    public function completeIfFinished(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking): bool {
            $locked = $this->lockBooking($booking);
            if ($locked->status !== 'confirmada' || ! $locked->ends_at || $locked->ends_at->isFuture()) {
                return false;
            }
            $locked->update(['status' => 'realizada']);

            return true;
        }, 3);
    }

    public function delete(Booking $booking): void
    {
        DB::transaction(function () use ($booking): void {
            $locked = $this->lockBooking($booking);
            if (! $locked->canBeEdited() || $locked->google_event_id) {
                throw ValidationException::withMessages(['booking' => 'No se puede eliminar. Cancela primero la reserva y comprueba su sincronización.']);
            }
            $locked->delete();
        }, 3);
    }
}
