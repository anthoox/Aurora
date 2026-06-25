<?php

namespace App\Observers;

use App\Models\ContactMessage;
use App\Models\User;
use Filament\Notifications\Notification;

class ContactMessageObserver
{
    public function created(ContactMessage $contactMessage): void
    {
        User::query()->each(function (User $user) use ($contactMessage) {
            Notification::make()
                ->title('Nuevo mensaje de contacto')
                ->body("{$contactMessage->first_name} ha enviado una consulta desde {$contactMessage->source?->name}.")
                ->icon('heroicon-o-envelope')
                ->info()
                ->sendToDatabase($user);
        });
    }
}