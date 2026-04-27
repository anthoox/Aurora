<?php

namespace App\Observers;

use App\Models\Interaction;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log; // Para que Log:: funcionen
use Illuminate\Support\Str; 

class InteractionObserver
{
    /**
     * Handle the Interaction "created" event.
     */
    public function created(Interaction $interaction): void
    {
    
        
        $admin = User::find(1);

        if ($admin) {
            try {
                $notification = Notification::make()
                    ->title('¡Nuevo Lead!')
                    ->body("Lead de: " . ($interaction->customer->first_name ?? 'Cliente'))
                    ->success()
                    ->icon('heroicon-o-bell');

                $message = $notification->getDatabaseMessage();
                $dataToSave = isset($message['data']) ? $message['data'] : $message;

                $admin->notifications()->create([
                    'id' => Str::uuid()->toString(),
                    'type' => 'Filament\Notifications\DatabaseNotification',
                    'data' => $dataToSave,
                    'read_at' => null,
                ]);

                Log::info('Notificación guardada con éxito');

            } catch (\Exception $e) {
                Log::error('Error en el Observer: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Interaction "updated" event.
     */
    public function updated(Interaction $interaction): void
    {
        //
    }

    /**
     * Handle the Interaction "deleted" event.
     */
    public function deleted(Interaction $interaction): void
    {
        //
    }

    /**
     * Handle the Interaction "restored" event.
     */
    public function restored(Interaction $interaction): void
    {
        //
    }

    /**
     * Handle the Interaction "force deleted" event.
     */
    public function forceDeleted(Interaction $interaction): void
    {
        //
    }
}
