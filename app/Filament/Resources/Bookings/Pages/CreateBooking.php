<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $startsAt = Carbon::parse($data['booking_date'] . ' ' . $data['start_time']);
        
        if ($startsAt->isPast()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'start_time' => 'La hora de inicio no puede ser anterior a la hora actual.',
            ]);
        }

        $data['starts_at'] = $startsAt;
        $data['ends_at'] = $startsAt->copy()->addHours((int) $data['duration_hours']);
        $data['requested_date'] = $data['booking_date'];

        unset($data['booking_date'], $data['start_time'], $data['duration_hours']);

        return $data;
    }
}

