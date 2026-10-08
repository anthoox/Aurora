<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Concerns\HandlesBookingValidation;
use App\Filament\Resources\Bookings\BookingResource;
use App\Services\BookingScheduler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBooking extends CreateRecord
{
    use HandlesBookingValidation;

    protected static string $resource = BookingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $scheduler = app(BookingScheduler::class);

        return $this->runBookingOperation(fn () => $scheduler->createManual($scheduler->formData($data)));
    }
}
