<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Models\Interaction;
use App\Models\Service;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Interactions\InteractionResource;
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
            Action::make('convertToLead')
                ->label('Convertir a lead')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn() => $this->record->status !== 'convertido')
                ->form([
                    Select::make('service_id')
                        ->label('Servicio')
                        ->options(function () {
                            if (!$this->record->source_id) {
                                return Service::query()
                                    ->pluck('name', 'id');
                            }

                            return Service::query()
                                ->whereHas('sources', function ($query) {
                                    $query
                                        ->where('sources.id', $this->record->source_id)
                                        ->where('service_source.is_active', true);
                                })
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('status')
                        ->label('Estado inicial del lead')
                        ->options([
                            'nuevo' => 'Nuevo',
                            'contactado' => 'Contactado',
                        ])
                        ->default('nuevo')
                        ->required(),

                    Textarea::make('notes')
                        ->label('Notas internas')
                        ->default(fn() => "Lead creado desde mensaje de contacto.\n\nMensaje original:\n{$this->record->message}")
                        ->rows(5),
                ])
                ->requiresConfirmation()
                ->modalHeading('Convertir contacto a lead')
                ->modalDescription('Se creará una nueva interacción comercial asociada a este cliente.')
                ->action(function (array $data): void {
                    $interaction = Interaction::create([
                        'customer_id' => $this->record->customer_id,
                        'source_id' => $this->record->source_id,
                        'service_id' => $data['service_id'],
                        'status' => $data['status'],
                        'notes' => $data['notes'] ?? null,
                        'origin_type' => 'contact',
                        'message' => $this->record->message,
                    ]);

                    $this->record->update([
                        'status' => 'convertido',
                        'converted_at' => now(),
                    ]);

                    $this->redirect(
                        InteractionResource::getUrl('view', [
                            'record' => $interaction,
                        ])
                    );
                }),
            EditAction::make(),
        ];
    }
}