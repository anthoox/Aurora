<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingSlotUnavailableException extends ValidationException
{
    public const CODE = 'booking_slot_unavailable';

    public const MESSAGE = 'La hora seleccionada ya no está disponible.';

    public static function create(): self
    {
        return self::withMessages([
            'booking_time' => self::MESSAGE,
        ]);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => self::MESSAGE,
            'code' => self::CODE,
            'errors' => $this->errors(),
        ], 422);
    }
}
