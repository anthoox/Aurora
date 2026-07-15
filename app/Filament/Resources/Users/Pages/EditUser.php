<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $selectedRole = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->record->getRoleNames()->first();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->selectedRole = $this->form->getRawState()['role']
            ?? $this->record->getRoleNames()->first()
            ?? 'operator';

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncRoles([
            $this->selectedRole ?? 'operator',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn(): bool => $this->record->isNot(auth()->user())),
        ];
    }
}