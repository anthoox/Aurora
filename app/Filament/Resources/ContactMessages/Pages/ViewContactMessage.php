<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('email')
                ->label('Email')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->disabled(fn() => blank($this->record->email))
                ->tooltip(
                    fn() => blank($this->record->email)
                    ? 'Este contacto no tiene email'
                    : 'Responder por email'
                )
                ->url(function () {
                    $subject = 'Respuesta a tu consulta';
                    $body = "Hola {$this->record->first_name},\n\n"
                        . "Te contacto en relación con tu consulta.";

                    return 'mailto:' . $this->record->email
                        . '?subject=' . rawurlencode($subject)
                        . '&body=' . rawurlencode($body);
                }),
            Action::make('markAsResponded')
                ->label('Marcar como respondido')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->record->status === 'nuevo')
                ->requiresConfirmation()
                ->modalHeading('Marcar contacto como respondido')
                ->modalDescription('El contacto pasará a estado respondido y se guardará la fecha de respuesta.')
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'respondido',
                        'responded_at' => now(),
                    ]);
                }),
            Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->disabled(fn() => blank($this->record->phone))
                ->tooltip(
                    fn() => blank($this->record->phone)
                    ? 'Este contacto no tiene teléfono'
                    : 'Responder por WhatsApp'
                )
                ->url(function () {
                    $phone = preg_replace('/\D+/', '', $this->record->phone);

                    $message = "Hola {$this->record->first_name}, te contacto en relación con tu consulta.";

                    return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
                })
                ->openUrlInNewTab(),

            EditAction::make(),
        ];
    }
}