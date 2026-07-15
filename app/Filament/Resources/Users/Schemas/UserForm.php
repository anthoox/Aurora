<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del usuario')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('role')
                            ->label('Rol')
                            ->options([
                                'admin' => 'Administrador',
                                'manager' => 'Manager',
                                'operator' => 'Operador',
                            ])
                            ->default('operator')
                            ->required()
                            ->native(false)
                            ->dehydrated(false),

                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                            ->helperText(
                                fn(string $operation): string => $operation === 'edit'
                                ? 'Déjala vacía para conservar la contraseña actual.'
                                : 'Introduce una contraseña segura.'
                            ),
                    ])
                    ->columns(2),
            ]);
    }
}