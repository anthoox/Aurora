<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Interactions\InteractionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use App\Filament\Resources\Customers\Pages\ViewCustomer;

class InteractionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    protected static ?string $relatedResource = InteractionResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewCustomer::class;
    }
}
