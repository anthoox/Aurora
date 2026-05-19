<?php

namespace App\Filament\Widgets;

use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Interaction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
class LatestLeads extends TableWidget
{
    // Esto define el orden (2º)
    protected static ?int $sort = 2;

    // Ocupa 2 de las 3 columnas disponibles
    protected int|string|array $columnSpan = 2;
    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Traemos las últimas 5 interacciones
                Interaction::query()->latest()->limit(5)
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Hora')
                    ->dateTime('H:i')
                    ->sortable(),
                TextColumn::make('customer.first_name')
                    ->label('Cliente'),
                TextColumn::make('customer.email')
                    ->label('Email'),
                TextColumn::make('source.name')
                    ->label('Origen')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('service.name')
                    ->label('Servicio')
                    ->placeholder('No especificado'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'nuevo' => 'info',
                        'contactado' => 'warning',
                        'reservado' => 'primary',
                        'vendido' => 'success',
                        'descartado' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                // Usando la ruta completa evitas conflictos de nombres
                Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-m-eye')
                    ->url(fn(Interaction $record): string => "/admin/interactions/{$record->getKey()}")
            ]);
    }
}
