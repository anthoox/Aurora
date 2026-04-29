<?php

namespace App\Filament\Resources\Interactions;

use App\Filament\Resources\Interactions\Pages\CreateInteraction;
use App\Filament\Resources\Interactions\Pages\EditInteraction;
use App\Filament\Resources\Interactions\Pages\ListInteractions;
use App\Filament\Resources\Interactions\Schemas\InteractionForm;
use App\Filament\Resources\Interactions\Tables\InteractionsTable;
use App\Models\Interaction;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InteractionResource extends Resource
{
    protected static ?string $model = Interaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InteractionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InteractionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInteractions::route('/'),
            'create' => CreateInteraction::route('/create'),
            'edit' => EditInteraction::route('/{record}/edit'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumen del lead')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge(),

                        TextEntry::make('service.name')
                            ->label('Servicio'),

                        TextEntry::make('source.name')
                            ->label('Origen'),

                        TextEntry::make('created_at')
                            ->label('Fecha de entrada')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(4),

                Section::make('Cliente')
                    ->schema([
                        TextEntry::make('customer.first_name')
                            ->label('Nombre'),

                        TextEntry::make('customer.last_name')
                            ->label('Apellidos'),

                        TextEntry::make('customer.email')
                            ->label('Email'),

                        TextEntry::make('customer.phone')
                            ->label('Teléfono')
                            ->placeholder('Sin teléfono'),
                    ])
                    ->columns(2),

                Section::make('Solicitud')
                    ->schema([
                        TextEntry::make('message')
                            ->label('Mensaje del cliente')
                            ->placeholder('Sin mensaje')
                            ->columnSpanFull(),
                    ]),

                Section::make('Gestión interna')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notas internas')
                            ->placeholder('Sin notas internas')
                            ->columnSpanFull(),

                        TextEntry::make('updated_at')
                            ->label('Última actualización')
                            ->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }
}
