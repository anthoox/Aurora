<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Customers\Pages\ViewCustomer;

class BookingsRelationManager extends RelationManager
{
  protected static string $relationship = 'bookings';

  protected static ?string $title = 'Reservas futuras';

  public function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        DateTimePicker::make('starts_at')
          ->label('Fecha y hora de inicio')
          ->required(),

        DateTimePicker::make('ends_at')
          ->label('Fecha y hora de fin'),

        Select::make('service_id')
          ->label('Servicio')
          ->relationship('service', 'name')
          ->searchable()
          ->preload(),

        Select::make('source_id')
          ->label('Origen')
          ->relationship('source', 'name')
          ->searchable()
          ->preload(),

        Select::make('status')
          ->label('Estado')
          ->options([
            'pendiente' => 'Pendiente',
            'confirmada' => 'Confirmada',
            'cancelada' => 'Cancelada',
            'realizada' => 'Realizada',
          ])
          ->default('pendiente')
          ->required(),

        Textarea::make('notes')
          ->label('Notas')
          ->columnSpanFull(),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->modifyQueryUsing(
        fn(Builder $query) => $query
          ->where('starts_at', '>=', now())
          ->orderBy('starts_at')
      )
      ->columns([
        TextColumn::make('starts_at')
          ->label('Inicio')
          ->dateTime('d/m/Y H:i')
          ->sortable(),

        TextColumn::make('ends_at')
          ->label('Fin')
          ->dateTime('d/m/Y H:i')
          ->placeholder('Sin fecha fin'),

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
            'cancelada' => 'danger',
            'realizada' => 'info',
            default => 'gray',
          }),
      ])
      ->headerActions([
        CreateAction::make()
          ->label('Nueva reserva'),
      ])
      ->actions([
        EditAction::make(),
        DeleteAction::make(),
      ]);
  }

  public static function canViewForRecord($ownerRecord, string $pageClass): bool
  {
    return $pageClass === ViewCustomer::class;
  }
}