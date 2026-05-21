<?php

namespace App\Filament\Resources\Interactions\Tables;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InteractionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('customer.email')
                    ->label('Cliente')
                    ->description(fn($record): string => trim(
                        "{$record->customer?->first_name} {$record->customer?->last_name}"
                    ))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('customer', function (Builder $query) use ($search) {
                            $query
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                TextColumn::make('source.name')
                    ->label('Origen')
                    ->badge()
                    ->sortable(),

                // TextColumn::make('origin_type')
                //     ->label('Entrada')
                //     ->badge()
                //     ->formatStateUsing(fn(?string $state): string => match ($state) {
                //         'api' => 'Web/API',
                //         'manual' => 'Manual',
                //         'booking' => 'Reserva',
                //         default => 'Manual',
                //     })
                //     ->color(fn(?string $state): string => match ($state) {
                //         'api' => 'success',
                //         'manual' => 'gray',
                //         'booking' => 'info',
                //         default => 'gray',
                //     }),

                TextColumn::make('follow_up_status')
                    ->label('Seguimiento')
                    ->state(function ($record): string {
                        if (
                            in_array($record->status, ['nuevo', 'contactado'])
                            && !$record->bookings()->exists()
                            && $record->updated_at <= now()->subHours(24)
                        ) {
                            return 'Pendiente';
                        }

                        return 'OK';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Pendiente' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('service.name')
                    ->label('Servicio')
                    ->placeholder('Sin servicio'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'nuevo' => 'gray',
                        'contactado' => 'info',
                        'reservado' => 'warning',
                        'vendido' => 'success',
                        'descartado' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('bookings_count')
                    ->label('Reserva')
                    ->counts('bookings')
                    ->formatStateUsing(fn($state) => $state > 0 ? 'Con reserva' : 'Sin reserva')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'gray'),

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
                        'reservado' => 'Reservado',
                        'vendido' => 'Vendido',
                        'descartado' => 'Descartado',
                    ]),

                SelectFilter::make('source_id')
                    ->label('Origen')
                    ->relationship('source', 'name'),

                SelectFilter::make('service_id')
                    ->label('Servicio')
                    ->relationship('service', 'name'),

                SelectFilter::make('origin_type')
                    ->label('Entrada')
                    ->options([
                        'api' => 'Web/API',
                        'manual' => 'Manual',
                        'booking' => 'Reserva',
                    ]),

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

                Filter::make('pending_follow_up')
                    ->label('Pendientes de seguimiento')
                    ->query(
                        fn(Builder $query): Builder => $query
                            ->whereIn('status', ['nuevo', 'contactado'])
                            ->whereDoesntHave('bookings')
                            ->where('updated_at', '<=', now()->subHours(24))
                    ),
            ])
            ->actions([
                Action::make('viewBooking')
                    ->label('Reserva')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->visible(fn($record) => $record->bookings()->exists())
                    ->url(fn($record) => BookingResource::getUrl('view', [
                        'record' => $record->bookings()->latest()->first(),
                    ])),

                ViewAction::make()
                    ->label('Ver'),

                EditAction::make()
                    ->label('Editar')
                    ->disabled(fn($record) => !$record->canBeEdited())
                    ->tooltip(
                        fn($record) => $record->canBeEdited()
                        ? 'Editar interacción'
                        : 'Esta interacción ya está cerrada'
                    )
                    ->color(fn($record) => $record->canBeEdited() ? 'primary' : 'gray'),
            ]);
    }
}