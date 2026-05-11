<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Models\Service;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos principales')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'email')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('interaction_id')
                            ->label('Lead relacionado')
                            ->relationship('interaction', 'id')
                            ->searchable()
                            ->preload()
                            ->placeholder('Sin lead relacionado'),

                        Select::make('source_id')
                            ->label('Origen')
                            ->relationship('source', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($set) => $set('service_id', null)),

                        Select::make('service_id')
                            ->label('Servicio')
                            ->options(function ($get) {
                                $sourceId = $get('source_id');

                                if (!$sourceId) {
                                    return [];
                                }

                                return Service::query()
                                    ->whereHas('sources', function ($query) use ($sourceId) {
                                        $query
                                            ->where('sources.id', $sourceId)
                                            ->where('service_source.is_active', true);
                                    })
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Selecciona primero un origen'),
                    ])
                    ->columns(2),

                Section::make('Fecha y estado')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Inicio')
                            ->seconds(false)
                            ->required(),

                        DateTimePicker::make('ends_at')
                            ->label('Fin')
                            ->seconds(false),

                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pendiente' => 'Pendiente',
                                'confirmada' => 'Confirmada',
                                'cancelada' => 'Cancelada',
                                'realizada' => 'Realizada',
                            ])
                            ->default('pendiente')
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Notas')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas internas')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}