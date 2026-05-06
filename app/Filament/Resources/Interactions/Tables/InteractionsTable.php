<?php

namespace App\Filament\Resources\Interactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;


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
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'nuevo' => 'Nuevo',
                        'contactado' => 'Contactado',
                        'vendido' => 'Vendido',
                        'descartado' => 'Descartado',
                    ]),
                SelectFilter::make('source_id')
                    ->label('Origen')
                    ->relationship('source', 'name'),
                SelectFilter::make('service_id')
                    ->label('Servicio')
                    ->relationship('service', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}