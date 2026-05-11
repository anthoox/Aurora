<?php

namespace App\Filament\Resources\Interactions\Pages;

use App\Filament\Resources\Interactions\InteractionResource;

use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Illuminate\Support\Str;
use App\Models\Booking;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class ViewInteraction extends ViewRecord
{
  protected static string $resource = InteractionResource::class;

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
        ->action(function () {
          $phone = preg_replace('/\D+/', '', $this->record->customer->phone);

          $serviceName = $this->record->service?->name ?? 'tu consulta';
          $sourceName = $this->record->source?->name ?? 'nuestra web';

          $message = "Hola {$this->record->customer->first_name}, te contacto desde {$sourceName} por tu consulta sobre {$serviceName}.";

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

          return redirect()->away(
            'https://wa.me/' . $phone . '?text=' . urlencode($message)
          );
        }),

      Action::make('createBooking')
        ->label('Crear reserva')
        ->icon('heroicon-o-calendar-days')
        ->color('primary')
        ->form([
          DateTimePicker::make('starts_at')
            ->label('Inicio')
            ->seconds(false)
            ->required(),

          DateTimePicker::make('ends_at')
            ->label('Fin')
            ->seconds(false),

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
          Booking::create([
            'customer_id' => $this->record->customer_id,
            'interaction_id' => $this->record->id,
            'source_id' => $this->record->source_id,
            'service_id' => $this->record->service_id,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
          ]);
        }),

      EditAction::make(),
    ];
  }

  
}