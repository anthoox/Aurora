<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingAlreadyExistsException extends ValidationException
{
    public const CODE = 'booking_already_exists';

    public const MESSAGE = 'Ya existe una solicitud activa para este servicio y fecha u horario.';

    public static function create(): self
    {
        return self::withMessages([
            'booking' => self::MESSAGE,
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
