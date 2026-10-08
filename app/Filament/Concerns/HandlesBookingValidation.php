<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

trait HandlesBookingValidation
{
    protected function runBookingOperation(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (ValidationException $exception) {
            Notification::make()->title('No se pudo guardar la reserva')
                ->body(collect($exception->errors())->flatten()->implode(' '))
                ->danger()->send();
            throw $exception;
        }
    }
}
