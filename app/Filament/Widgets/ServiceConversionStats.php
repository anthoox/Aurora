<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Filament\Widgets\Widget;

class ServiceConversionStats extends Widget
{
    protected string $view = 'filament.widgets.service-conversion-stats';

    protected int|string|array $columnSpan = 'full';

    public function getServices()
    {
        return Service::query()
            ->withCount([
                'interactions',

                'interactions as vendidos_count' => fn($query) =>
                    $query->where('status', 'vendido'),
            ])
            ->get()
            ->map(function ($service) {

                $service->conversion_rate =
                    $service->interactions_count > 0
                    ? round(
                        ($service->vendidos_count / $service->interactions_count) * 100,
                        1
                    )
                    : 0;

                return $service;
            })
            ->sortByDesc('conversion_rate');
    }
}