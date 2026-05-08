<?php

namespace App\Filament\Resources\Interactions\Pages;

use App\Filament\Resources\Interactions\InteractionResource;

use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Illuminate\Support\Str;

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
            'https://wa.me/' . $phone . '?text=' . urlencode($message),
            
          );
        }),
      EditAction::make(),

    ];
  }

  
}