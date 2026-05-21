<?php

namespace App\Filament\Resources\Interactions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InteractionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gestión del lead')
                    ->schema([
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'nuevo' => 'Nuevo',
                                'contactado' => 'Contactado',
                                'reservado' => 'Reservado',
                                'vendido' => 'Vendido',
                                'descartado' => 'Descartado',
                            ])
                            ->required(),

                        Textarea::make('notes')
                            ->label('Notas internas')
                            ->rows(6)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }
}