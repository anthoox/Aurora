<?php

namespace App\Filament\Widgets;

use App\Models\Interaction;
use App\Models\Customer;
use App\Models\Source;
use App\Models\Booking;
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


        $leadsPendientes = Interaction::query()
            ->whereIn('status', ['nuevo', 'contactado'])
            ->whereDoesntHave('bookings')
            ->where('updated_at', '<=', now()->subHours(24))
            ->count();

        $reservasConfirmadas = Booking::where('status', 'confirmada')->count();

        $reservasHoy = Booking::whereDate('starts_at', today())->count();

        $leadsVendidos = Interaction::where('status', 'vendido')->count();

        $totalLeads = Interaction::count();

        $leadsMensuales = Interaction::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $tasaConversion = $totalLeads > 0
            ? round(($leadsVendidos / $totalLeads) * 100, 1)
            : 0;

        return [


            // Stat::make('Clientes Únicos', Customer::count())
            //     ->description('Base de datos total')
            //     ->descriptionIcon('heroicon-m-users')
            //     ->color('primary'),

            Stat::make('Leads de Hoy', $leadsHoy)
                ->description($leadsHoy > 0 ? '¡Día productivo!' : 'Esperando entradas...')
                ->descriptionIcon($leadsHoy > 0 ? 'heroicon-m-bolt' : 'heroicon-m-clock')
                ->color($leadsHoy > 0 ? 'info' : 'gray'),

            // Stat::make('Nuevos Clientes Hoy', $clientesNuevosHoy)
            //     ->description('Personas que no conocíamos')
            //     ->descriptionIcon('heroicon-m-user-plus')
            //     ->color($clientesNuevosHoy > 0 ? 'warning' : 'gray'),

            Stat::make('Leads mensuales', $leadsMensuales)
                ->description('Entradas del mes actual')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),


            // Stat::make('Fuentes Activas', Source::where('is_active', true)->count())
            //     ->description('Webs conectadas')
            //     ->descriptionIcon('heroicon-m-globe-alt'),


            Stat::make('Leads pendientes', $leadsPendientes)
                ->description('Sin seguimiento en +24h')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($leadsPendientes > 0 ? 'warning' : 'success'),

            // Stat::make('Reservas confirmadas', $reservasConfirmadas)
            //     ->description('Total confirmadas')
            //     ->descriptionIcon('heroicon-m-calendar-days')
            //     ->color('success'),

            Stat::make('Reservas hoy', $reservasHoy)
                ->description('Citas programadas para hoy')
                ->descriptionIcon('heroicon-m-clock')
                ->color($reservasHoy > 0 ? 'info' : 'gray'),

            Stat::make('Conversión', $tasaConversion . '%')
                ->description('Leads vendidos / total leads')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($tasaConversion > 0 ? 'success' : 'gray'),


        ];
    }
}