<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumen de reserva')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Reserva')
                            ->formatStateUsing(fn ($state) => 'RES-'.$state),

                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pendiente' => 'warning',
                                'confirmada' => 'success',
                                'cancelada' => 'danger',
                                'realizada' => 'info',
                                default => 'gray',
                            }),

                        TextEntry::make('starts_at')
                            ->label('Fecha y hora')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Hora pendiente de asignar'),

                        TextEntry::make('requested_date')
                            ->label('Fecha solicitada')
                            ->date('d/m/Y')
                            ->placeholder('Sin fecha solicitada'),

                        TextEntry::make('booking_mode')
                            ->label('Modalidad')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'date_only' => 'Solicitud por fecha',
                                'time_slots' => 'Reserva con horario',
                                default => $state,
                            }),

                        TextEntry::make('duration')
                            ->label('Duración')
                            ->state(function ($record): string {
                                if (! $record->starts_at || ! $record->ends_at) {
                                    return 'Sin duración';
                                }

                                $minutes = (int) $record->starts_at->diffInMinutes($record->ends_at);

                                if ($minutes < 60) {
                                    return "{$minutes} minutos";
                                }

                                $hours = intdiv($minutes, 60);
                                $remainingMinutes = $minutes % 60;

                                return $remainingMinutes === 0
                                    ? ($hours === 1 ? '1 hora' : "{$hours} horas")
                                    : "{$hours} h {$remainingMinutes} min";
                            }),
                        TextEntry::make('participants_count')
                            ->label('Participantes')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('language')
                            ->label('Idioma')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'es' => 'Español',
                                'en' => 'Inglés',
                                'fr' => 'Francés',
                                'de' => 'Alemán',
                                'it' => 'Italiano',
                                default => '-',
                            }),

                        TextEntry::make('level')
                            ->label('Nivel')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'beginner' => 'Principiante',
                                'intermediate' => 'Intermedio',
                                'advanced' => 'Avanzado',
                                'professional' => 'Profesional',
                                default => '-',
                            }),
                    ])
                    ->columns(4),

                Section::make('Cliente')
                    ->schema([
                        TextEntry::make('customer.first_name')
                            ->label('Nombre'),

                        TextEntry::make('customer.last_name')
                            ->label('Apellidos')
                            ->placeholder('Sin apellidos'),

                        TextEntry::make('customer.email')
                            ->label('Email'),

                        TextEntry::make('customer.phone')
                            ->label('Teléfono')
                            ->placeholder('Sin teléfono'),
                    ])
                    ->columns(2),

                Section::make('Servicio y origen')
                    ->schema([
                        TextEntry::make('service.name')
                            ->label('Servicio')
                            ->placeholder('Sin servicio'),

                        TextEntry::make('source.name')
                            ->label('Origen')
                            ->badge()
                            ->placeholder('Sin origen'),

                        TextEntry::make('interaction.id')
                            ->label('Lead relacionado')
                            ->formatStateUsing(fn ($state) => $state ? 'INT-'.$state : null)
                            ->placeholder('Reserva directa'),
                    ])
                    ->columns(3),

                Section::make('Google Calendar')
                    ->schema([
                        TextEntry::make('google_event_id')
                            ->label('Evento Google')
                            ->placeholder('No sincronizado'),

                        TextEntry::make('google_synced_at')
                            ->label('Última sincronización')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Sin sincronizar'),
                    ])
                    ->columns(2),

                Section::make('Notas internas')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('')
                            ->placeholder('Sin notas internas')
                            ->columnSpanFull(),
                    ]),

                Section::make('Mensaje del cliente')
                    ->schema([
                        TextEntry::make('customer_message')
                            ->label('')
                            ->placeholder('Sin mensaje del cliente')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
