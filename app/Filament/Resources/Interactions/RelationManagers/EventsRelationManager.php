<?php

namespace App\Filament\Resources\Interactions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Resources\Interactions\Pages\ViewInteraction;
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
            'status_changed' => 'Cambio de estado',
            'note_updated' => 'Nota actualizada',
            'booking_created' => 'Reserva creada',
            'booking_updated' => 'Reserva actualizada',
            'booking_status_changed' => 'Estado de reserva',
            default => $state,
          })
          ->color(fn(string $state): string => match ($state) {
            'status_changed' => 'info',
            'note_updated' => 'gray',
            'booking_created' => 'success',
            'booking_updated' => 'warning',
            'booking_status_changed' => 'warning',
            default => 'gray',
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

  public static function canViewForRecord($ownerRecord, string $pageClass): bool
  {
    return $pageClass === ViewInteraction::class;
  }
}