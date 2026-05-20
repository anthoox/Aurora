<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->disabled(fn() => blank($this->record->customer->phone))
                ->tooltip(
                    fn() => blank($this->record->customer->phone)
                    ? 'Este cliente no tiene teléfono registrado'
                    : 'Contactar por WhatsApp'
                )
                ->url(function () {
                    $phone = preg_replace('/\D+/', '', $this->record->customer->phone);

                    $serviceName = $this->record->service?->name ?? 'la reserva';
                    $date = $this->record->starts_at?->format('d/m/Y H:i');

                    $message = "Hola {$this->record->customer->first_name}, te contacto sobre tu reserva de {$serviceName} del {$date}.";

                    return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
                })
                ->openUrlInNewTab(),

            Action::make('email')
                ->label('Email')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->disabled(fn() => blank($this->record->customer->email))
                ->tooltip(
                    fn() => blank($this->record->customer->email)
                    ? 'Este cliente no tiene email registrado'
                    : 'Contactar por email'
                )
                ->url(function () {
                    $serviceName = $this->record->service?->name ?? 'tu reserva';
                    $date = $this->record->starts_at?->format('d/m/Y H:i');

                    $subject = "Reserva de {$serviceName}";
                    $body = "Hola {$this->record->customer->first_name},\n\n"
                        . "Te contacto sobre tu reserva de {$serviceName} del {$date}.";

                    return 'mailto:' . $this->record->customer->email
                        . '?subject=' . rawurlencode($subject)
                        . '&body=' . rawurlencode($body);
                }),

            EditAction::make()
                ->disabled(fn() => !$this->record->canBeEdited())
                ->tooltip(
                    fn() => $this->record->canBeEdited()
                    ? 'Editar reserva'
                    : 'Esta reserva no se puede editar por su estado o porque ya ha finalizado'
                )
                ->color(fn() => $this->record->canBeEdited() ? 'primary' : 'gray'),
        ];
    }
}