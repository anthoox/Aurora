<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('metadata'),
            ]);
    }
}
