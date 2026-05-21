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
                                'descartado' => 'Descartado',
                            ])
                            ->required()
                            ->rules([
                                function ($get, $record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($record) {

                                        if (
                                            $value === 'descartado'
                                            && $record
                                            && $record->bookings()
                                                ->whereIn('status', ['pendiente', 'confirmada'])
                                                ->exists()
                                        ) {
                                            $fail('No puedes descartar este lead porque tiene una reserva activa.');
                                        }
                                    };
                                },
                            ]),

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