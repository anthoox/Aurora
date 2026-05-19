<?php

namespace App\Filament\Resources\Interactions\Pages;

use App\Filament\Resources\Interactions\InteractionResource;

use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Illuminate\Support\Str;
use App\Models\Booking;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use App\Filament\Resources\Bookings\BookingResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Carbon\Carbon;
class ViewInteraction extends ViewRecord
{
  protected static string $resource = InteractionResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Action::make('viewBooking')
        ->label('Ver reserva')
        ->icon('heroicon-o-eye')
        ->color('info')
        ->visible(fn() => $this->record->bookings()->exists())
        ->url(fn() => BookingResource::getUrl('view', [
          'record' => $this->record->bookings()->latest()->first(),
        ])),
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

          $serviceName = $this->record->service?->name ?? 'tu consulta';
          $sourceName = $this->record->source?->name ?? 'nuestra web';

          $message = "Hola {$this->record->customer->first_name}, te contacto desde {$sourceName} por tu consulta sobre {$serviceName}.";

          // timeline
          $this->record->events()->create([
            'user_id' => auth()->id(),
            'type' => 'whatsapp_opened',
            'description' => 'Conversación de WhatsApp iniciada',
            'new_value' => $phone,
            'metadata' => [
              'phone' => $phone,
              'message' => $message,
            ],
          ]);

          // automatización comercial
          if ($this->record->status === 'nuevo') {
            $this->record->update([
              'status' => 'contactado',
            ]);
          }

          return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
        })
        ->openUrlInNewTab(),

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

          Select::make('duration_hours')
            ->label('Duración')
            ->options([
              1 => '1 hora',
              2 => '2 horas',
              3 => '3 horas',
              4 => '4 horas',
              5 => '5 horas',
            ])
            ->default(1)
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
        ->disabled(fn() => $this->record->bookings()->exists())
        ->tooltip(
          fn() => $this->record->bookings()->exists()
          ? 'Este lead ya tiene una reserva asociada'
          : 'Crear reserva para este lead'
        )
        ->action(function (array $data): void {
          $startsAt = Carbon::parse($data['booking_date'] . ' ' . $data['start_time']);

          $endsAt = $startsAt->copy()->addHours((int) $data['duration_hours']);

          Booking::create([
            'customer_id' => $this->record->customer_id,
            'interaction_id' => $this->record->id,
            'source_id' => $this->record->source_id,
            'service_id' => $this->record->service_id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
          ]);

          $this->record->update([
            'status' => 'reservado',
          ]);
        }),

      EditAction::make(),
    ];
  }

  
}