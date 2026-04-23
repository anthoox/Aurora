<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    // app/Filament/Resources/Customers/Schemas/CustomerForm.php

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('last_name')
                    ->label('Apellidos'),
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true), // Evita duplicar el mismo email
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel(),
            ]);
    }
}
