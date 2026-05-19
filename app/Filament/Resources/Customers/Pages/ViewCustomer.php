<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->disabled(fn() => blank($this->record->phone))
                ->tooltip(
                    fn() => blank($this->record->phone)
                    ? 'Este cliente no tiene teléfono registrado'
                    : 'Contactar por WhatsApp'
                )
                ->url(function () {
                    $phone = preg_replace('/\D+/', '', $this->record->phone);

                    $message = "Hola {$this->record->first_name}, te contacto desde Aurora.";

                    return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
                })
                ->openUrlInNewTab(),

            Action::make('email')
                ->label('Email')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->disabled(fn() => blank($this->record->email))
                ->tooltip(
                    fn() => blank($this->record->email)
                    ? 'Este cliente no tiene email registrado'
                    : 'Contactar por email'
                )
                ->url(function () {
                    $subject = 'Contacto desde Aurora';
                    $body = "Hola {$this->record->first_name},\n\nTe contacto en relación con tu solicitud.";

                    return 'mailto:' . $this->record->email
                        . '?subject=' . rawurlencode($subject)
                        . '&body=' . rawurlencode($body);
                }),

            EditAction::make(),
        ];
    }
}

