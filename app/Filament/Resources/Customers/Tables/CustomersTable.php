<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Cliente')
                    ->state(fn($record): string => trim(
                        "{$record->first_name} {$record->last_name}"
                    ))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name']),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->placeholder('Sin teléfono')
                    ->copyable(),

                TextColumn::make('interactions_count')
                    ->label('Leads')
                    ->counts('interactions')
                    ->badge()
                    ->color(fn($state): string => $state > 0 ? 'info' : 'gray'),

                TextColumn::make('bookings_count')
                    ->label('Reservas')
                    ->counts('bookings')
                    ->badge()
                    ->color(fn($state): string => $state > 0 ? 'success' : 'gray'),

                TextColumn::make('updated_at')
                    ->label('Última actividad')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}