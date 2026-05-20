<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Reserva')
                    ->formatStateUsing(fn($state) => 'RES-' . $state)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.email')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('source.name')
                    ->label('Origen')
                    ->badge()
                    ->placeholder('Sin origen')
                    ->sortable(),

                TextColumn::make('service.name')
                    ->label('Servicio')
                    ->placeholder('Sin servicio')
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('Fecha Reserva')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('duration')
                    ->label('Duración')
                    ->state(function ($record): string {
                        if (!$record->starts_at || !$record->ends_at) {
                            return 'Sin duración';
                        }

                        $hours = $record->starts_at->diffInHours($record->ends_at);

                        return $hours === 1
                            ? '1 hora'
                            : "{$hours} horas";
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'confirmada' => 'success',
                        'cancelada' => 'danger',
                        'realizada' => 'info',
                        default => 'gray',
                    }),
                    
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),


                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
