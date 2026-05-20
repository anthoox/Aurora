<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ServiceConversionStats extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Conversión por servicio';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Service::query()
                    ->withCount([
                        'interactions',
                        'interactions as vendidos_count' => fn(Builder $query) =>
                            $query->where('status', 'vendido'),
                    ])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Servicio')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('interactions_count')
                    ->label('Leads')
                    ->sortable(),

                TextColumn::make('vendidos_count')
                    ->label('Vendidos')
                    ->sortable(),

                TextColumn::make('conversion_rate')
                    ->label('Conversión')
                    ->state(function ($record): string {
                        if ($record->interactions_count === 0) {
                            return '0%';
                        }

                        return round(($record->vendidos_count / $record->interactions_count) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color(function ($record): string {
                        if ($record->interactions_count === 0) {
                            return 'gray';
                        }

                        $rate = ($record->vendidos_count / $record->interactions_count) * 100;

                        return match (true) {
                            $rate >= 70 => 'success',
                            $rate >= 30 => 'warning',
                            default => 'danger',
                        };
                    }),
            ])
            ->defaultSort('vendidos_count', 'desc');
    }
}