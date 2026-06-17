<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Timeline';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'booking_created' => 'Reserva creada',
                        'status_changed' => 'Cambio de estado',
                        'date_changed' => 'Cambio de fecha',
                        'notes_changed' => 'Notas actualizadas',
                        'participants_changed' => 'Participantes',
                        'language_changed' => 'Idioma',
                        'level_changed' => 'Nivel',
                        default => $state,
                    }),

                TextColumn::make('description')
                    ->label('Descripción')
                    ->wrap(),

                TextColumn::make('old_value')
                    ->label('Antes')
                    ->placeholder('-')
                    ->limit(60),

                TextColumn::make('new_value')
                    ->label('Después')
                    ->placeholder('-')
                    ->limit(60),

                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('Sistema'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}