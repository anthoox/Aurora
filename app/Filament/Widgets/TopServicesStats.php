<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Filament\Widgets\Widget;

class TopServicesStats extends Widget
{
    protected string $view = 'filament.widgets.top-services-stats';

    protected int|string|array $columnSpan = 'full';

    public function getServices()
    {
        return Service::query()
            ->withCount([
                'bookings as completed_bookings_count' => fn($query) =>
                    $query->where('status', 'realizada'),
            ])
            ->orderByDesc('completed_bookings_count')
            ->get();
    }
}