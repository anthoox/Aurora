<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de contacto')
                    ->schema([
                        TextEntry::make('first_name')
                            ->label('Nombre'),

                        TextEntry::make('last_name')
                            ->label('Apellidos')
                            ->placeholder('Sin apellidos'),

                        TextEntry::make('email')
                            ->label('Email'),

                        TextEntry::make('phone')
                            ->label('Teléfono')
                            ->placeholder('Sin teléfono'),

                        TextEntry::make('source.name')
                            ->label('Origen')
                            ->badge()
                            ->placeholder('Sin origen'),

                        TextEntry::make('created_at')
                            ->label('Fecha de entrada')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),

                Section::make('Estado')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'nuevo' => 'Nuevo',
                                'respondido' => 'Respondido',
                                'convertido' => 'Convertido',
                                'archivado' => 'Archivado',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'nuevo' => 'warning',
                                'respondido' => 'info',
                                'convertido' => 'success',
                                'archivado' => 'gray',
                                default => 'gray',
                            }),

                        TextEntry::make('responded_at')
                            ->label('Respondido el')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('No respondido'),

                        TextEntry::make('converted_at')
                            ->label('Convertido el')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('No convertido'),
                    ])
                    ->columns(3),

                Section::make('Mensaje')
                    ->schema([
                        TextEntry::make('message')
                            ->label('')
                            ->placeholder('Sin mensaje')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}