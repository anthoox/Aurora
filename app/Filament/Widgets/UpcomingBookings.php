<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingBookings extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Próximas reservas';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->whereIn('status', ['pendiente', 'confirmada'])
                    ->where('starts_at', '>=', now())
                    ->orderBy('starts_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('customer.email')
                    ->label('Cliente'),


                // TextColumn::make('source.name')
                //     ->label('Origen')
                //     ->badge()
                //     ->color('gray'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'confirmada' => 'success',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-m-eye')
                    ->url(fn(Booking $record): string => "/admin/bookings/{$record->getKey()}"),
            ]);
    }
}