<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Source;
use App\Services\BookingAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AvailabilityController extends Controller
{
    public function __invoke(
        Request $request,
        BookingAvailabilityService $availabilityService,
    ): JsonResponse {
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
            'service_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $service = Service::find($data['service_id']);

        if (! $service) {
            throw ValidationException::withMessages([
                'service_id' => 'El servicio no está disponible para esta fuente.',
            ]);
        }

        $date = CarbonImmutable::parse($data['date'], config('app.timezone'))->startOfDay();
        $availability = $availabilityService->availabilityFor($source, $service, $date);

        return response()->json([
            'data' => [
                'date' => $date->toDateString(),
                'service_id' => $service->id,
                'duration_minutes' => $availability['duration_minutes'],
                'slots' => $availability['slots']
                    ->map(fn (CarbonImmutable $slot): string => $slot->format('H:i'))
                    ->values()
                    ->all(),
            ],
        ]);
    }
}
