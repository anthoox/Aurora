<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Models\Service;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos principales')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'email')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('interaction_id')
                            ->label('Lead relacionado')
                            ->relationship('interaction', 'id')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($record) => filled($record?->interaction_id))
                            ->disabled()
                            ->dehydrated(),

                        Select::make('source_id')
                            ->label('Origen')
                            ->relationship('source', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('service_id', null)),

                        Select::make('service_id')
                            ->label('Servicio')
                            ->options(function ($get) {
                                $sourceId = $get('source_id');

                                if (! $sourceId) {
                                    return [];
                                }

                                return Service::query()
                                    ->whereHas('sources', function ($query) use ($sourceId) {
                                        $query
                                            ->where('sources.id', $sourceId)
                                            ->where('service_source.is_active', true);
                                    })
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Selecciona primero un origen')
                            ->required(fn ($record) => ! $record),
                        Select::make('participants_count')
                            ->label('Participantes')
                            ->options([
                                1 => '1 participante',
                                2 => '2 participantes',
                                3 => '3 participantes',
                                4 => '4 participantes',
                                5 => '5 participantes',
                                6 => '6 participantes',
                                7 => '7 participantes',
                                8 => '8 participantes',
                                9 => '9 participantes',
                                10 => '10 participantes',
                                15 => '15 participantes',
                                20 => '20 participantes',
                            ])
                            ->default(1)
                            ->required(),

                        Select::make('language')
                            ->label('Idioma')
                            ->options([
                                'es' => 'EspaÃ±ol',
                                'en' => 'InglÃ©s',
                                'fr' => 'FrancÃ©s',
                                'de' => 'AlemÃ¡n',
                                'it' => 'Italiano',
                            ])
                            ->searchable(),

                        Select::make('level')
                            ->label('Nivel')
                            ->options([
                                'beginner' => 'Principiante',
                                'intermediate' => 'Intermedio',
                                'advanced' => 'Avanzado',
                                'professional' => 'Profesional',
                            ])
                            ->searchable(),
                    ])

                    ->columns(2),

                Section::make('Fecha y estado')
                    ->schema([
                        DatePicker::make('booking_date')
                            ->label('Fecha')
                            ->native(false)
                            ->minDate(fn ($record) => $record ? null : today())
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record?->starts_at || $record?->requested_date) {
                                    $component->state(
                                        ($record->starts_at ?? $record->requested_date)->toDateString()
                                    );
                                }
                            }),

                        TimePicker::make('start_time')
                            ->label('Hora inicio')
                            ->seconds(false)
                            ->afterStateHydrated(function ($component, $record) {
                                $component->state($record?->starts_at?->format('H:i'));
                            }),

                        TextInput::make('duration_minutes')
                            ->label('Duración en minutos')
                            ->numeric()->integer()->minValue(1)
                            ->default(60)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record?->starts_at && $record?->ends_at) {
                                    $component->state((int) $record->starts_at->diffInMinutes($record->ends_at));
                                }
                            }),

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
                    ])
                    ->columns(3),

                Section::make('Notas')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas internas')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
