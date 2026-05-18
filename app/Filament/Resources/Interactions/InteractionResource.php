<?php

namespace App\Filament\Resources\Interactions;

use App\Filament\Resources\Interactions\Pages\CreateInteraction;
use App\Filament\Resources\Interactions\Pages\EditInteraction;
use App\Filament\Resources\Interactions\Pages\ListInteractions;
use App\Filament\Resources\Interactions\Pages\ViewInteraction;
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
use App\Filament\Resources\Interactions\RelationManagers\EventsRelationManager;
class InteractionResource extends Resource
{
    protected static ?string $model = Interaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    public static function form(Schema $schema): Schema
    {
        return InteractionForm::configure($schema);
    }
    protected static ?string $navigationLabel = 'Interacciones';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    public static function table(Table $table): Table
    {
        return InteractionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInteractions::route('/'),
            'create' => CreateInteraction::route('/create'),
            'view' => ViewInteraction::route('/{record}'),
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
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'nuevo' => 'gray',
                                'contactado' => 'info',
                                'vendido' => 'success',
                                'reservado' => 'warning',
                                'descartado' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('service.name')
                            ->label('Servicio'),

                        TextEntry::make('source.name')
                            ->label('Origen'),

                        TextEntry::make('created_at')
                            ->label('Fecha de entrada')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),

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
                    ])                    ->columns(2),


                Section::make('Gestión interna')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notas internas')
                            ->placeholder('Sin notas internas')
                            ->columnSpanFull(),

                        TextEntry::make('updated_at')
                            ->label('Última actualización')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(2),

                Section::make('Cliente recurrente')
                    ->visible(
                        fn($record) =>
                        $record->customer
                            ->interactions()
                            ->where('id', '!=', $record->id)
                            ->count() > 0
                    )
                    ->schema([
                        TextEntry::make('customer.id')
                            ->label('')
                            ->formatStateUsing(function ($record) {
                                $count = $record->customer
                                    ->interactions()
                                    ->where('id', '!=', $record->id)
                                    ->count();

                                return "⚠ Este cliente tiene {$count} solicitudes previas.";
                            }),
                    ])
                    ->compact(),
                TextEntry::make('catalog_price')
                    ->label('Precio')
                    ->money('EUR')
                    ->placeholder('Sin precio configurado'),
                TextEntry::make('catalog_description')
                    ->label('Descripción del servicio')
                    ->placeholder('Sin descripción personalizada')
                    ->columnSpanFull(),
            ]);
    }
}
