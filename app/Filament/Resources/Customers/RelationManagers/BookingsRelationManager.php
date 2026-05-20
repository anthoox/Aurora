<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsRelationManager extends RelationManager
{
  protected static string $relationship = 'bookings';

  protected static ?string $title = 'Próximas reservas';

  public function table(Table $table): Table
  {
    return $table
      ->modifyQueryUsing(
        fn(Builder $query) => $query
          ->where('starts_at', '>=', now())
          ->whereIn('status', ['pendiente', 'confirmada'])
          ->orderBy('starts_at')
      )
      ->columns([
        TextColumn::make('id')
          ->label('Reserva')
          ->formatStateUsing(fn($state) => 'RES-' . $state),

        TextColumn::make('starts_at')
          ->label('Fecha')
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

        TextColumn::make('service.name')
          ->label('Servicio')
          ->placeholder('Sin servicio'),

        TextColumn::make('source.name')
          ->label('Origen')
          ->badge()
          ->placeholder('Sin origen'),

        TextColumn::make('status')
          ->label('Estado')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'pendiente' => 'warning',
            'confirmada' => 'success',
            default => 'gray',
          }),
      ])
      ->headerActions([
        CreateAction::make()
          ->label('Nueva reserva'),
      ])
      ->recordActions([
        ViewAction::make()
          ->url(fn($record) => BookingResource::getUrl('view', [
            'record' => $record,
          ])),

        EditAction::make()
          ->disabled(fn($record) => !$record->canBeEdited())
          ->tooltip(
            fn($record) => $record->canBeEdited()
            ? 'Editar reserva'
            : 'Esta reserva no se puede editar por su estado o porque ya ha empezado'
          )
          ->color(fn($record) => $record->canBeEdited() ? 'primary' : 'gray'),
      ]);
  }

  public static function canViewForRecord($ownerRecord, string $pageClass): bool
  {
    return $pageClass === ViewCustomer::class;
  }
}