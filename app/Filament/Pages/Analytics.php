<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;

class Analytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Analytics';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.analytics';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\SourceConversionStats::class,
        ];
    }
}