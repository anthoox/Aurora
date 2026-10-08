<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Concerns\HandlesBookingValidation;
use App\Filament\Resources\Bookings\BookingResource;
use App\Services\BookingScheduler;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBooking extends EditRecord
{
    use HandlesBookingValidation;

    protected static string $resource = BookingResource::class;

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        if (! $this->record->canBeEdited()) {
            abort(403, 'Esta reserva no se puede editar.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->disabled(fn (): bool => (bool) $this->record->google_event_id)
                ->tooltip('Cancela primero si existe un evento de Google Calendar.')
                ->using(function (Model $record): bool {
                    $this->runBookingOperation(fn () => app(BookingScheduler::class)->delete($record));

                    return true;
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $scheduler = app(BookingScheduler::class);

        return $this->runBookingOperation(fn () => $scheduler->updateManual($record, $scheduler->formData($data, $record)));
    }
}
