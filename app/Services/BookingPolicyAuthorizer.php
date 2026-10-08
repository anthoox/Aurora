<?php

namespace App\Services;

use App\Models\Source;
use Illuminate\Validation\ValidationException;

class BookingPolicyAuthorizer
{
    public function authorize(Source $source, string $policy): void
    {
        $allowed = config('bookings.source_availability_policies', [])[$source->getKey()]
            ?? ['blocking'];

        if (! in_array($policy, ['blocking', 'non_blocking'], true)
            || ! in_array($policy, $allowed, true)) {
            throw ValidationException::withMessages([
                'availability_policy' => 'La política no está autorizada para esta fuente.',
            ]);
        }
    }
}
