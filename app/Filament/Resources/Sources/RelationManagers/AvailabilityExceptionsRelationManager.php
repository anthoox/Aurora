<?php

namespace App\Filament\Resources\Sources\RelationManagers;

use App\Models\SourceAvailabilityException;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class AvailabilityExceptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'availabilityExceptions';

    protected static ?string $title = 'Excepciones por fecha';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('date')
            ->columns([
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('is_closed')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Cerrado' : 'Horario especial')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'info'),

                TextColumn::make('opens_at')
                    ->label('Apertura')
                    ->time('H:i')
                    ->placeholder('—'),

                TextColumn::make('closes_at')
                    ->label('Cierre')
                    ->time('H:i')
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('date')
            ->headerActions([
                CreateAction::make()
                    ->label('Añadir excepción')
                    ->form(self::formSchema())
                    ->using(fn (array $data): SourceAvailabilityException => $this->createException($data))
                    ->successNotificationTitle('Excepción creada'),
            ])
            ->recordActions([
                EditAction::make()
                    ->form(self::formSchema())
                    ->using(fn (SourceAvailabilityException $record, array $data): SourceAvailabilityException => $this->updateException($record, $data))
                    ->successNotificationTitle('Excepción actualizada'),
                DeleteAction::make()
                    ->successNotificationTitle('Excepción eliminada'),
            ]);
    }

    private static function formSchema(): array
    {
        return [
            DatePicker::make('date')
                ->label('Fecha')
                ->native(false)
                ->minDate(today())
                ->required(),

            Toggle::make('is_closed')
                ->label('Día completamente cerrado')
                ->helperText('Si se activa, no habrá disponibilidad durante esta fecha.')
                ->live()
                ->default(false),

            TimePicker::make('opens_at')
                ->label('Hora de apertura especial')
                ->seconds(false)
                ->minutesStep(15)
                ->visible(fn (Get $get): bool => ! $get('is_closed'))
                ->required(fn (Get $get): bool => ! $get('is_closed')),

            TimePicker::make('closes_at')
                ->label('Hora de cierre especial')
                ->seconds(false)
                ->minutesStep(15)
                ->after('opens_at')
                ->visible(fn (Get $get): bool => ! $get('is_closed'))
                ->required(fn (Get $get): bool => ! $get('is_closed')),

            Toggle::make('is_active')
                ->label('Activo')
                ->default(true)
                ->required(),
        ];
    }

    private function createException(array $data): SourceAvailabilityException
    {
        try {
            return $this->getOwnerRecord()->availabilityExceptions()->create($data);
        } catch (ValidationException $exception) {
            $this->sendValidationWarning($exception);

            throw $exception;
        }
    }

    private function updateException(
        SourceAvailabilityException $availabilityException,
        array $data,
    ): SourceAvailabilityException {
        try {
            $availabilityException->update($data);

            return $availabilityException;
        } catch (ValidationException $exception) {
            $this->sendValidationWarning($exception);

            throw $exception;
        }
    }

    private function sendValidationWarning(ValidationException $exception): void
    {
        Notification::make()
            ->title('No se pudo guardar la excepción')
            ->body(collect($exception->errors())->flatten()->first())
            ->warning()
            ->persistent()
            ->send();
    }
}
