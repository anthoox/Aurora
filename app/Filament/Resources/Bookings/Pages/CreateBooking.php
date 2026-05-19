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

        $data['starts_at'] = $startsAt;
        $data['ends_at'] = $startsAt->copy()->addHours((int) $data['duration_hours']);

        unset($data['booking_date'], $data['start_time'], $data['duration_hours']);

        return $data;
    }
}


