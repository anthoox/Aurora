<?php

namespace App\Filament\Resources\Interactions\Pages;

use App\Filament\Concerns\HandlesBookingValidation;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Interactions\InteractionResource;
use App\Services\BookingScheduler;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewInteraction extends ViewRecord
{
    use HandlesBookingValidation;

    protected static string $resource = InteractionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewBooking')
                ->label('Ver reserva')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn () => $this->record->bookings()->exists())
                ->url(fn () => BookingResource::getUrl('view', [
                    'record' => $this->record->bookings()->latest()->first(),
                ])),
            Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->disabled(fn () => blank($this->record->customer->phone))
                ->tooltip(
                    fn () => blank($this->record->customer->phone)
                    ? 'Este cliente no tiene telÃƒÂ©fono registrado'
                    : 'Contactar por WhatsApp'
                )
                ->url(function () {
                    $phone = preg_replace('/\D+/', '', $this->record->customer->phone);

                    $serviceName = $this->record->service?->name ?? 'tu consulta';
                    $sourceName = $this->record->source?->name ?? 'nuestra web';

                    $message = "Hola {$this->record->customer->first_name}, te contacto desde {$sourceName} por tu consulta sobre {$serviceName}.";

                    return 'https://wa.me/'.$phone.'?text='.urlencode($message);
                })
                ->openUrlInNewTab(),
            Action::make('email')
                ->label('Email')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->disabled(fn () => blank($this->record->customer->email))
                ->tooltip(
                    fn () => blank($this->record->customer->email)
                    ? 'Este cliente no tiene email registrado'
                    : 'Contactar por email'
                )
                ->url(function () {
                    $serviceName = $this->record->service?->name ?? 'tu consulta';
                    $sourceName = $this->record->source?->name ?? 'nuestra web';

                    $subject = "Consulta sobre {$serviceName}";
                    $body = "Hola {$this->record->customer->first_name},\n\n"
                      ."Te contacto desde {$sourceName} por tu consulta sobre {$serviceName}.";

                    return 'mailto:'.$this->record->customer->email
                      .'?subject='.rawurlencode($subject)
                      .'&body='.rawurlencode($body);
                }),

            Action::make('createBooking')
                ->label('Crear reserva')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->form([
                    DatePicker::make('booking_date')
                        ->label('Fecha')
                        ->native(false)
                        ->required()
                        ->minDate(now()),

                    Select::make('start_time')
                        ->label('Hora inicio')
                        ->options([
                            '09:00' => '09:00',
                            '09:30' => '09:30',
                            '10:00' => '10:00',
                            '10:30' => '10:30',
                            '11:00' => '11:00',
                            '11:30' => '11:30',
                            '12:00' => '12:00',
                            '12:30' => '12:30',
                            '13:00' => '13:00',
                            '13:30' => '13:30',
                            '14:00' => '14:00',
                            '14:30' => '14:30',
                            '15:00' => '15:00',
                            '15:30' => '15:30',
                            '16:00' => '16:00',
                            '16:30' => '16:30',
                            '17:00' => '17:00',
                            '17:30' => '17:30',
                            '18:00' => '18:00',
                            '18:30' => '18:30',
                            '19:00' => '19:00',
                            '19:30' => '19:30',
                            '20:00' => '20:00',
                        ])
                        ->searchable()
                        ->required(),

                    Select::make('duration_minutes')
                        ->label('DuraciÃƒÂ³n')
                        ->options([
                            30 => '30 minutos',
                            45 => '45 minutos',
                            60 => '1 hora',
                            90 => '90 minutos',
                            120 => '2 horas',
                            180 => '3 horas',
                            240 => '4 horas',
                            300 => '5 horas',
                        ])
                        ->default(60)
                        ->required(),

                    Select::make('status')
                        ->label('Estado')
                        ->options([
                            'pendiente' => 'Pendiente',
                            'confirmada' => 'Confirmada',
                            'cancelada' => 'Cancelada',
                            'realizada' => 'Realizada',
                        ])
                        ->default('pendiente')
                        ->required(),

                    Textarea::make('notes')
                        ->label('Notas internas')
                        ->rows(4),
                ])
                ->disabled(fn () => $this->record->bookings()->exists())
                ->tooltip(
                    fn () => $this->record->bookings()->exists()
                    ? 'Este lead ya tiene una reserva asociada'
                    : 'Crear reserva para este lead'
                )
                ->action(function (array $data): void {
                    $scheduler = app(BookingScheduler::class);
                    $this->runBookingOperation(fn () => $scheduler->createFromInteraction($this->record, $scheduler->formData($data)));

                }),

            EditAction::make()
                ->disabled(fn () => ! $this->record->canBeEdited())
                ->tooltip(
                    fn () => $this->record->canBeEdited()
                    ? 'Editar interacciÃƒÂ³n'
                    : 'Esta interacciÃƒÂ³n ya estÃƒÂ¡ cerrada'
                )
                ->color(fn () => $this->record->canBeEdited() ? 'primary' : 'gray'),
        ];
    }
}
