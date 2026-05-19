<?php

namespace App\Filament\Widgets;

use App\Models\Source;
use Filament\Widgets\Widget;

class SourceConversionStats extends Widget
{
    protected string $view = 'filament.widgets.source-conversion-stats';

    protected int|string|array $columnSpan = 'full';
    protected static bool $isDiscovered = false;

    public function getSources()
    {
        return Source::query()
            ->withCount([
                'interactions',
                'interactions as vendidos_count' => fn($query) =>
                    $query->where('status', 'vendido'),
            ])
            ->get()
            ->map(function ($source) {
                $source->conversion_rate =
                    $source->interactions_count > 0
                    ? round(($source->vendidos_count / $source->interactions_count) * 100, 1)
                    : 0;

                return $source;
            });
    }
}