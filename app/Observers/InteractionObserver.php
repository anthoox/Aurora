<?php

namespace App\Observers;

use App\Models\Interaction;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log; // Para que Log:: funcionen
use Illuminate\Support\Str; 
use Filament\Actions\Action;
use App\Filament\Resources\Interactions\InteractionResource;
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
                    ->body("Lead de: " . ($interaction->customer->first_name . ' (' . $interaction->customer->email . ')' ?? 'Cliente'))
                    ->success()
                    ->icon('heroicon-o-bell')
                    ->actions([
                        Action::make('view')
                            ->label('Ver Lead')
                            ->url(fn() => InteractionResource::getUrl('view', ['record' => $interaction]))
                            ->markAsRead(), // Opcional: marca como leída al clicar
                    ]);

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
        if ($interaction->wasChanged('status')) {
            $interaction->events()->create([
                'user_id' => auth()->id(),
                'type' => 'status_changed',
                'description' => "Estado cambiado de {$interaction->getOriginal('status')} a {$interaction->status}",
                'old_value' => $interaction->getOriginal('status'),
                'new_value' => $interaction->status,
            ]);
        }

        if ($interaction->wasChanged('notes')) {
            $oldNotes = $interaction->getOriginal('notes');
            $newNotes = $interaction->notes;

            $recentDuplicate = $interaction->events()
                ->where('type', 'note_updated')
                ->where('old_value', $oldNotes ? Str::limit($oldNotes, 100) : null)
                ->where('new_value', $newNotes ? Str::limit($newNotes, 100) : null)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->exists();

            if (!$recentDuplicate) {
                $interaction->events()->create([
                    'user_id' => auth()->id(),
                    'type' => 'note_updated',
                    'description' => 'Notas internas actualizadas',
                    'old_value' => $oldNotes
                        ? Str::limit($oldNotes, 100)
                        : null,
                    'new_value' => $newNotes
                        ? Str::limit($newNotes, 100)
                        : null,
                ]);
            }
        }
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
