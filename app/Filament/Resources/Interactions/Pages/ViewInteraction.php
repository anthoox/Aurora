<?php

namespace App\Filament\Resources\Interactions\Pages;

use App\Filament\Resources\Interactions\InteractionResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;

class ViewInteraction extends ViewRecord
{
  protected static string $resource = InteractionResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
    ];
  }
}