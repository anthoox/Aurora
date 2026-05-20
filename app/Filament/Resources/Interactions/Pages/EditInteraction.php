<?php

namespace App\Filament\Resources\Interactions\Pages;

use App\Filament\Resources\Interactions\InteractionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\ViewAction;


class EditInteraction extends EditRecord
{
    protected static string $resource = InteractionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

        ];
    }
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        if (!$this->record->canBeEdited()) {
            abort(403, 'Esta interacción no se puede editar.');
        }
    }
}
