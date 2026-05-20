<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Interactions\InteractionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class InteractionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    protected static ?string $title = 'Historial de leads';

    protected static ?string $relatedResource = InteractionResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo lead'),
            ]);
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewCustomer::class;
    }
}