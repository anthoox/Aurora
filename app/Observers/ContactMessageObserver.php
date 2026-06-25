<?php

namespace App\Observers;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContactMessageObserver
{
    public function created(ContactMessage $contactMessage): void
    {
        $admin = User::find(1);

        if (!$admin) {
            return;
        }

        try {
            $notification = Notification::make()
                ->title('Nuevo mensaje de contacto')
                ->body(
                    'Consulta de: '
                    . $contactMessage->first_name
                    . ' ('
                    . $contactMessage->email
                    . ')'
                )
                ->info()
                ->icon('heroicon-o-envelope')
                ->actions([
                    Action::make('view')
                        ->label('Ver contacto')
                        ->url(fn() => ContactMessageResource::getUrl('view', [
                            'record' => $contactMessage,
                        ]))
                        ->markAsRead(),
                ]);

            $message = $notification->getDatabaseMessage();
            $dataToSave = isset($message['data']) ? $message['data'] : $message;

            $admin->notifications()->create([
                'id' => Str::uuid()->toString(),
                'type' => 'Filament\Notifications\DatabaseNotification',
                'data' => $dataToSave,
                'read_at' => null,
            ]);

            Log::info('Notificación de contacto guardada con éxito');
        } catch (\Exception $e) {
            Log::error('Error en ContactMessageObserver: ' . $e->getMessage());
        }
    }
}