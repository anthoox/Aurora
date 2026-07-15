<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $selectedRole = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedRole = $this->form->getRawState()['role'] ?? 'operator';

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncRoles([
            $this->selectedRole ?? 'operator',
        ]);
    }
}