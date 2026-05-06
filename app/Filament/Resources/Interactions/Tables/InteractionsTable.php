<?php

namespace App\Filament\Resources\Interactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

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
                Filter::make('created_at')
                    ->label('Fecha')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Desde'),

                        DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder =>
                                $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder =>
                                $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Filter::make('sin_contactar')
                    ->label('Sin contactar')
                    ->query(fn(Builder $query): Builder => $query->where('status', 'nuevo')),
                Filter::make('contactados')
                    ->label('Contactados')
                    ->query(fn(Builder $query): Builder => $query->where('status', 'contactado')),

                Filter::make('vendidos')
                    ->label('Vendidos')
                    ->query(fn(Builder $query): Builder => $query->where('status', 'vendido')),

                Filter::make('descartados')
                    ->label('Descartados')
                    ->query(fn(Builder $query): Builder => $query->where('status', 'descartado')),
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