<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Source;
use App\Rules\CustomFields;
use App\Services\BookingScheduler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')), 'UTF-8')]);
        }

        $data = $request->validate([
            'booking_mode' => ['required', Rule::in(['request_only', 'date_only', 'time_slots'])],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'service_id' => ['required', 'integer'],
            'requested_date' => ['exclude_if:booking_mode,request_only', 'required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'availability_policy' => ['exclude_unless:booking_mode,time_slots', 'sometimes', 'required', Rule::in(['blocking', 'non_blocking'])],
            'booking_time' => ['exclude_unless:booking_mode,time_slots', 'nullable', 'required_if:booking_mode,time_slots', 'date_format:H:i'],
            'customer_message' => ['nullable', 'string'],
            ...CustomFields::rules(),
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

        $booking = $bookingScheduler->createRequest($source, $service, $data);

        return response()->json([
            'message' => 'Solicitud de reserva recibida correctamente',
            'data' => [
                'id' => $booking->id,
                'booking_mode' => $booking->booking_mode,
                'status' => $booking->status,
                'requested_date' => $booking->requested_date?->toDateString(),
                'starts_at' => $booking->starts_at?->format('Y-m-d H:i:s'),
                'ends_at' => $booking->ends_at?->format('Y-m-d H:i:s'),
                'service_id' => $booking->service_id,
            ],
        ], 201);
    }
}
