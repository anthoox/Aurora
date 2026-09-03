<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Services\BookingScheduler;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scheduleAndConfirm')
                ->label('Asignar horario y confirmar')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->visible(fn (): bool => $this->record->booking_mode === 'date_only'
                    && $this->record->status === 'pendiente'
                    && ! $this->record->starts_at)
                ->form([
                    DatePicker::make('booking_date')
                        ->label('Fecha acordada')
                        ->default(fn () => $this->record->requested_date?->toDateString())
                        ->native(false)
                        ->minDate(today())
                        ->required(),

                    TimePicker::make('start_time')
                        ->label('Hora de inicio')
                        ->seconds(false)
                        ->minutesStep(15)
                        ->required(),

                    Select::make('duration_minutes')
                        ->label('Duración')
                        ->options([
                            30 => '30 minutos',
                            45 => '45 minutos',
                            60 => '1 hora',
                            90 => '1 hora y 30 minutos',
                            120 => '2 horas',
                        ])
                        ->default(60)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $startsAt = Carbon::parse($data['booking_date'].' '.$data['start_time']);

                    app(BookingScheduler::class)->scheduleAndConfirm(
                        $this->record,
                        $startsAt,
                        (int) $data['duration_minutes'],
                    );

                    Notification::make()
                        ->title('Horario asignado y reserva confirmada')
                        ->success()
                        ->send();

                    $this->redirect(BookingResource::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->disabled(fn () => blank($this->record->customer->phone))
                ->tooltip(
                    fn () => blank($this->record->customer->phone)
                    ? 'Este cliente no tiene teléfono registrado'
                    : 'Contactar por WhatsApp'
                )
                ->url(function () {
                    $phone = preg_replace('/\D+/', '', $this->record->customer->phone);

                    $serviceName = $this->record->service?->name ?? 'la reserva';
                    $date = $this->record->starts_at?->format('d/m/Y H:i')
                        ?? $this->record->requested_date?->format('d/m/Y').' (hora pendiente)';

                    $message = "Hola {$this->record->customer->first_name}, te contacto sobre tu reserva de {$serviceName} del {$date}.";

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
                    $serviceName = $this->record->service?->name ?? 'tu reserva';
                    $date = $this->record->starts_at?->format('d/m/Y H:i')
                        ?? $this->record->requested_date?->format('d/m/Y').' (hora pendiente)';

                    $subject = "Reserva de {$serviceName}";
                    $body = "Hola {$this->record->customer->first_name},\n\n"
                        ."Te contacto sobre tu reserva de {$serviceName} del {$date}.";

                    return 'mailto:'.$this->record->customer->email
                        .'?subject='.rawurlencode($subject)
                        .'&body='.rawurlencode($body);
                }),

            EditAction::make()
                ->disabled(fn () => ! $this->record->canBeEdited())
                ->tooltip(
                    fn () => $this->record->canBeEdited()
                    ? 'Editar reserva'
                    : 'Esta reserva no se puede editar por su estado o porque ya ha finalizado'
                )
                ->color(fn () => $this->record->canBeEdited() ? 'primary' : 'gray'),
        ];
    }
}
