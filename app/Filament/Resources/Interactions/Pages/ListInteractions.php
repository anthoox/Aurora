<?php

namespace App\Filament\Resources\Interactions\Pages;

use App\Filament\Resources\Interactions\InteractionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInteractions extends ListRecords
{
    protected static string $resource = InteractionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
