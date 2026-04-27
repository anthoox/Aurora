<?php

namespace App\Filament\Widgets;

use App\Models\Interaction;
use App\Models\Customer;
use App\Models\Source;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    // Corregido: eliminada la palabra 'static'
    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $leadsHoy = Interaction::whereDate('created_at', today())->count();

        return [
            Stat::make('Total Leads', Interaction::count())
                ->description('Histórico acumulado')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),

            Stat::make('Leads de Hoy', $leadsHoy)
                ->description($leadsHoy > 0 ? '¡Día productivo!' : 'Esperando entradas...')
                ->descriptionIcon($leadsHoy > 0 ? 'heroicon-m-bolt' : 'heroicon-m-clock')
                ->color($leadsHoy > 0 ? 'info' : 'gray'),

            Stat::make('Fuentes Activas', Source::where('is_active', true)->count())
                ->description('Webs conectadas')
                ->descriptionIcon('heroicon-m-globe-alt'),
        ];
    }
}