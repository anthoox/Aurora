<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Source;
use App\Services\BookingScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function store(Request $request, BookingScheduler $bookingScheduler): JsonResponse
    {
        $source = Source::query()
            ->where('api_token', $request->header('X-Aurora-Token'))
            ->where('is_active', true)
            ->first();

        if (! $source) {
            return response()->json([
                'error' => 'No autorizado o fuente inactiva',
            ], 401);
        }

        $data = $request->validate([
            'booking_mode' => ['required', Rule::in(['date_only', 'time_slots'])],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'service_id' => ['required', 'integer'],
            'requested_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'booking_time' => ['nullable', 'required_if:booking_mode,time_slots', 'date_format:H:i'],
            'customer_message' => ['nullable', 'string'],
        ]);

        $service = $source->services()
            ->whereKey($data['service_id'])
            ->wherePivot('is_active', true)
            ->first();

        if (! $service) {
            throw ValidationException::withMessages([
                'service_id' => 'El servicio no está disponible para esta fuente.',
            ]);
        }

        if ($data['booking_mode'] === 'time_slots') {
            $startsAt = CarbonImmutable::parse(
                $data['requested_date'].' '.$data['booking_time'],
                config('app.timezone'),
            );

            $booking = $bookingScheduler->createTimeSlotBooking(
                $source,
                $service,
                $startsAt,
                $data,
                $data['customer_message'] ?? null,
            );

            return response()->json([
                'message' => 'Reserva creada y confirmada correctamente',
                'data' => [
                    'id' => $booking->id,
                    'booking_mode' => $booking->booking_mode,
                    'status' => $booking->status,
                    'requested_date' => $booking->requested_date->toDateString(),
                    'starts_at' => $booking->starts_at->format('Y-m-d H:i:s'),
                    'ends_at' => $booking->ends_at->format('Y-m-d H:i:s'),
                    'service_id' => $booking->service_id,
                ],
            ], 201);
        }

        $booking = DB::transaction(function () use ($data, $source, $service): Booking {
            $customer = Customer::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ],
            );

            return Booking::create([
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'source_id' => $source->id,
                'booking_mode' => 'date_only',
                'requested_date' => $data['requested_date'],
                'starts_at' => null,
                'ends_at' => null,
                'status' => 'pendiente',
                'customer_message' => $data['customer_message'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Solicitud de reserva recibida correctamente',
            'data' => [
                'id' => $booking->id,
                'booking_mode' => $booking->booking_mode,
                'status' => $booking->status,
                'requested_date' => $booking->requested_date->toDateString(),
                'starts_at' => null,
                'ends_at' => null,
                'service_id' => $booking->service_id,
            ],
        ], 201);
    }
}
