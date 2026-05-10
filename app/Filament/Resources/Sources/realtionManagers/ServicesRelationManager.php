<?php

namespace App\Filament\Resources\Sources\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
class ServicesRelationManager extends RelationManager
{
  protected static string $relationship = 'services';

  protected static ?string $title = 'Catálogo de servicios';

  public function table(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')
          ->label('Servicio')
          ->searchable()
          ->sortable(),

        TextColumn::make('pivot.description')
          ->label('Descripción')
          ->limit(50)
          ->placeholder('Sin descripción'),

        TextColumn::make('pivot.price')
          ->label('Precio')
          ->money('EUR')
          ->placeholder('Sin precio'),

        IconColumn::make('pivot.is_active')
          ->label('Activo')
          ->boolean(),
      ])
      ->headerActions([
        AttachAction::make()
          ->label('Añadir servicio')
          ->preloadRecordSelect()
          ->form(fn(AttachAction $action): array => [
            $action->getRecordSelect(),

            Textarea::make('description')
              ->label('Descripción personalizada')
              ->rows(3),

            TextInput::make('price')
              ->label('Precio')
              ->numeric()
              ->prefix('€'),

            Toggle::make('is_active')
              ->label('Activo')
              ->default(true),
          ]),
      ])
      ->actions([
        Action::make('editCatalog')
          ->label('Editar catálogo')
          ->icon('heroicon-o-pencil-square')
          ->form([
            Textarea::make('description')
              ->label('Descripción personalizada')
              ->rows(3),

            TextInput::make('price')
              ->label('Precio')
              ->numeric()
              ->prefix('€'),

            Toggle::make('is_active')
              ->label('Activo'),
          ])
          ->fillForm(fn($record): array => [
            'description' => $record->pivot->description,
            'price' => $record->pivot->price,
            'is_active' => $record->pivot->is_active,
          ])
          ->action(function ($record, array $data): void {
            $this->getOwnerRecord()
              ->services()
              ->updateExistingPivot($record->id, [
                'description' => $data['description'],
                'price' => $data['price'],
                'is_active' => $data['is_active'],
              ]);
      $this->dispatch('$refresh');

          }),
          
      ]);
  }
  public static function getRelations(): array
  {
    return [
      ServicesRelationManager::class,
    ];
  }

  
}