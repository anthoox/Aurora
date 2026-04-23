<?php

namespace App\Filament\Resources\Interactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn; // Por si quieres editar el estado desde la tabla
use Filament\Tables\Table;

class InteractionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Mostramos el email del cliente a través de la relación 'customer'
                TextColumn::make('customer.email')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                // Mostramos el nombre de la web de origen
                TextColumn::make('source.name')
                    ->label('Origen')
                    ->badge() // Le da un estilo visual de etiqueta
                    ->sortable(),

                // Mostramos el servicio
                TextColumn::make('service.name')
                    ->label('Servicio')
                    ->placeholder('Sin servicio'),

                // Mostramos el estado con colores
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'nuevo' => 'gray',
                        'contactado' => 'info',
                        'vendido' => 'success',
                        'descartado' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // Aquí podrías añadir un filtro por estado más adelante
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}