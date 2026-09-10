<?php

namespace App\Filament\Resources\Sources\RelationManagers;

use App\Models\SourceOpeningHour;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

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
                    ->form(self::formSchema())
                    ->using(fn (array $data): SourceOpeningHour => $this->createOpeningHour($data))
                    ->successNotificationTitle('Tramo horario creado'),
            ])
            ->recordActions([
                EditAction::make()
                    ->form(self::formSchema())
                    ->using(fn (SourceOpeningHour $record, array $data): SourceOpeningHour => $this->updateOpeningHour($record, $data))
                    ->successNotificationTitle('Tramo horario actualizado'),
                DeleteAction::make()
                    ->successNotificationTitle('Tramo horario eliminado'),
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
                ->after('opens_at')
                ->required(),

            Toggle::make('is_active')
                ->label('Activo')
                ->default(true)
                ->required(),
        ];
    }

    private function createOpeningHour(array $data): SourceOpeningHour
    {
        try {
            return $this->getOwnerRecord()->openingHours()->create($data);
        } catch (ValidationException $exception) {
            $this->sendValidationWarning($exception);

            throw $exception;
        }
    }

    private function updateOpeningHour(SourceOpeningHour $openingHour, array $data): SourceOpeningHour
    {
        try {
            $openingHour->update($data);

            return $openingHour;
        } catch (ValidationException $exception) {
            $this->sendValidationWarning($exception);

            throw $exception;
        }
    }

    private function sendValidationWarning(ValidationException $exception): void
    {
        Notification::make()
            ->title('No se pudo guardar el tramo horario')
            ->body(collect($exception->errors())->flatten()->first())
            ->warning()
            ->persistent()
            ->send();
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
