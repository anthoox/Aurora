<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('Contacto')
                    ->description(fn($record): string => trim(
                        "{$record->first_name} {$record->last_name}"
                    ))
                    ->searchable(['email', 'first_name', 'last_name', 'phone'])
                    ->sortable(),

                TextColumn::make('source.name')
                    ->label('Origen')
                    ->badge()
                    ->placeholder('Sin origen'),

                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->placeholder('Sin teléfono')
                    ->copyable(),

                TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(60)
                    ->wrap(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'nuevo' => 'warning',
                        'respondido' => 'info',
                        'convertido' => 'success',
                        'archivado' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'nuevo' => 'Nuevo',
                        'respondido' => 'Respondido',
                        'convertido' => 'Convertido',
                        'archivado' => 'Archivado',
                        default => $state,
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
                        'respondido' => 'Respondido',
                        'convertido' => 'Convertido',
                        'archivado' => 'Archivado',
                    ]),

                SelectFilter::make('source_id')
                    ->label('Origen')
                    ->relationship('source', 'name'),

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
            ])
            ->actions([
                ViewAction::make()
                    ->label('Ver'),

                EditAction::make()
                    ->label('Gestionar'),
            ]);
    }
}