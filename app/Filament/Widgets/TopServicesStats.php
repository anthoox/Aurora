<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopServicesStats extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Servicios más vendidos';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Service::query()
                    ->withCount([
                        'bookings as completed_bookings_count' => fn(Builder $query) =>
                            $query->where('status', 'realizada'),
                    ])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Servicio')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('completed_bookings_count')
                    ->label('Reservas realizadas')
                    ->sortable()
                    ->badge()
                    ->color(fn($state): string => $state > 0 ? 'success' : 'gray'),
            ])
            ->defaultSort('completed_bookings_count', 'desc');
    }
}