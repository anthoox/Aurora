<?php

namespace App\Filament\Resources\Bookings\Tables;

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
                    ->formatStateUsing(fn ($state) => 'RES-'.$state)
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
                TextColumn::make('service.created_at')
                    ->label('Fecha creación')
                    ->placeholder('Sin servicio')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Fecha Reserva')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Hora pendiente')
                    ->sortable(),

                TextColumn::make('requested_date')
                    ->label('Fecha solicitada')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('duration')
                    ->label('Duración')
                    ->state(function ($record): string {
                        if (! $record->starts_at || ! $record->ends_at) {
                            return 'Sin duración';
                        }

                        $minutes = (int) $record->starts_at->diffInMinutes($record->ends_at);

                        if ($minutes < 60) {
                            return "{$minutes} minutos";
                        }

                        $hours = intdiv($minutes, 60);
                        $remainingMinutes = $minutes % 60;

                        return $remainingMinutes === 0
                            ? ($hours === 1 ? '1 hora' : "{$hours} horas")
                            : "{$hours} h {$remainingMinutes} min";
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'confirmada' => 'success',
                        'cancelada' => 'danger',
                        'realizada' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('booking_origin')
                    ->label('Tipo')
                    ->state(fn ($record): string => $record->interaction_id ? 'Desde lead' : 'Manual')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Desde lead' => 'info',
                        'Manual' => 'gray',
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
                EditAction::make()
                    ->disabled(fn ($record) => ! $record->canBeEdited())
                    ->tooltip(
                        fn ($record) => $record->canBeEdited()
                        ? 'Editar reserva'
                        : 'Esta reserva no se puede editar por su estado o porque ya ha finalizado'
                    )
                    ->color(fn ($record) => $record->canBeEdited() ? 'primary' : 'gray'),
            ]);
    }
}
