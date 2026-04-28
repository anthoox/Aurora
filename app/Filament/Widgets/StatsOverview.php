<?php

namespace App\Filament\Widgets;

use App\Models\Interaction;
use App\Models\Customer;
use App\Models\Source;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '15s';
    protected static ?int $sort = 1;

    // Esto dice que en una rejilla de 3, ocupe solo 1 espacio (verticalmente se apilarán)
    protected int|string|array $columnSpan = 2;
    protected function getStats(): array
    {
        $trendData = collect(range(6, 0))->map(function ($daysAgo) {
            return Interaction::whereDate('created_at', Carbon::today()->subDays($daysAgo))->count();
        })->toArray();

        $leadsHoy = Interaction::whereDate('created_at', today())->count();
        $clientesNuevosHoy = Customer::whereDate('created_at', today())->count();
        $leadsHoy = Interaction::whereDate('created_at', today())->count();

        return [
            Stat::make('Total Leads', Interaction::count())
                ->description('Histórico acumulado')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->chart($trendData) // <-- ¡Datos reales aquí!
                ->color('success'),

            Stat::make('Clientes Únicos', Customer::count())
                ->description('Base de datos total')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Leads de Hoy', $leadsHoy)
                ->description($leadsHoy > 0 ? '¡Día productivo!' : 'Esperando entradas...')
                ->descriptionIcon($leadsHoy > 0 ? 'heroicon-m-bolt' : 'heroicon-m-clock')
                ->color($leadsHoy > 0 ? 'info' : 'gray'),

            Stat::make('Nuevos Clientes Hoy', $clientesNuevosHoy)
                ->description('Personas que no conocíamos')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color($clientesNuevosHoy > 0 ? 'warning' : 'gray'),




            Stat::make('Fuentes Activas', Source::where('is_active', true)->count())
                ->description('Webs conectadas')
                ->descriptionIcon('heroicon-m-globe-alt'),
        ];
    }
}