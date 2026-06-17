<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Models\Service;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                            ->visible(fn($record) => filled($record?->interaction_id))
                            ->disabled()
                            ->dehydrated(),

                        Select::make('source_id')
                            ->label('Origen')
                            ->relationship('source', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($set) => $set('service_id', null)),

                        Select::make('service_id')
                            ->label('Servicio')
                            ->options(function ($get) {
                                $sourceId = $get('source_id');

                                if (!$sourceId) {
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
                            ->required(),
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
                                'es' => 'Español',
                                'en' => 'Inglés',
                                'fr' => 'Francés',
                                'de' => 'Alemán',
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
                            ->minDate(today())
                            ->required()
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record?->starts_at) {
                                    $component->state(
                                        $record->starts_at->toDateString()
                                    );
                                }
                            }),

                        Select::make('start_time')
                            ->label('Hora inicio')
                            ->options([
                                '09:00' => '09:00',
                                '09:30' => '09:30',
                                '10:00' => '10:00',
                                '10:30' => '10:30',
                                '11:00' => '11:00',
                                '11:30' => '11:30',
                                '12:00' => '12:00',
                                '12:30' => '12:30',
                                '13:00' => '13:00',
                                '13:30' => '13:30',
                                '14:00' => '14:00',
                                '14:30' => '14:30',
                                '15:00' => '15:00',
                                '15:30' => '15:30',
                                '16:00' => '16:00',
                                '16:30' => '16:30',
                                '17:00' => '17:00',
                                '17:30' => '17:30',
                                '18:00' => '18:00',
                                '18:30' => '18:30',
                                '19:00' => '19:00',
                                '19:30' => '19:30',
                                '20:00' => '20:00',
                            ])
                            ->searchable()
                            ->required()
                            ->rule(function ($get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $date = $get('booking_date');

                                    if (!$date || !$value) {
                                        return;
                                    }

                                    $startsAt = \Carbon\Carbon::parse($date . ' ' . $value);

                                    if ($startsAt->isPast()) {
                                        $fail('La hora de inicio no puede ser anterior a la hora actual.');
                                    }
                                };
                            })
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record?->starts_at) {
                                    $component->state(
                                        $record->starts_at->format('H:i')
                                    );
                                }
                            }),

                        Select::make('duration_hours')
                            ->label('Duración')
                            ->options([
                                1 => '1 hora',
                                2 => '2 horas',
                                3 => '3 horas',
                                4 => '4 horas',
                                5 => '5 horas',
                            ])
                            ->default(1)
                            ->required()
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record?->starts_at && $record?->ends_at) {
                                    $component->state(
                                        $record->starts_at->diffInHours($record->ends_at)
                                    );
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