<?php

namespace App\Filament\Resources\Sources\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OpeningHoursRelationManager extends RelationManager
{
    protected static string $relationship = 'openingHours';

    protected static ?string $title = 'Horario semanal';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('day_of_week')
            ->columns([
                TextColumn::make('day_of_week')
                    ->label('Día')
                    ->formatStateUsing(fn (int $state): string => self::dayOptions()[$state])
                    ->sortable(),

                TextColumn::make('opens_at')
                    ->label('Apertura')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('closes_at')
                    ->label('Cierre')
                    ->time('H:i')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('day_of_week')
            ->headerActions([
                CreateAction::make()
                    ->label('Añadir tramo')
                    ->form(self::formSchema()),
            ])
            ->recordActions([
                EditAction::make()
                    ->form(self::formSchema()),
                DeleteAction::make(),
            ]);
    }

    private static function formSchema(): array
    {
        return [
            Select::make('day_of_week')
                ->label('Día de la semana')
                ->options(self::dayOptions())
                ->required()
                ->native(false),

            TimePicker::make('opens_at')
                ->label('Hora de apertura')
                ->seconds(false)
                ->minutesStep(15)
                ->required(),

            TimePicker::make('closes_at')
                ->label('Hora de cierre')
                ->seconds(false)
                ->minutesStep(15)
                ->required(),

            Toggle::make('is_active')
                ->label('Activo')
                ->default(true)
                ->required(),
        ];
    }

    private static function dayOptions(): array
    {
        return [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];
    }
}
