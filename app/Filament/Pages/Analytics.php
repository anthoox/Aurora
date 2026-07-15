<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;

class Analytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Analytics';

    protected static string|\UnitEnum|null $navigationGroup = 'Business';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.analytics';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\SourceConversionStats::class,
            \App\Filament\Widgets\TopServicesStats::class,
            \App\Filament\Widgets\ServiceConversionStats::class,
        ];
    }

    
}