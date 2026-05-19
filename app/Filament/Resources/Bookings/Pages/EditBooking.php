<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Carbon\Carbon;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $startsAt = Carbon::parse($data['booking_date'] . ' ' . $data['start_time']);

        $data['starts_at'] = $startsAt;
        $data['ends_at'] = $startsAt->copy()->addHours((int) $data['duration_hours']);

        unset($data['booking_date'], $data['start_time'], $data['duration_hours']);

        return $data;
    }
}
