<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Models\Customer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\Customers\RelationManagers\BookingsRelationManager;
class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InteractionsRelationManager::class,
            BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    protected static ?string $navigationLabel = 'Clientes';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos personales')
                    ->schema([
                        TextEntry::make('first_name')
                            ->label('Nombre'),

                        TextEntry::make('last_name')
                            ->label('Apellidos')
                            ->placeholder('Sin apellidos'),

                        TextEntry::make('email')
                            ->label('Email'),

                        TextEntry::make('phone')
                            ->label('Teléfono')
                            ->placeholder('Sin teléfono'),

                        TextEntry::make('created_at')
                            ->label('Cliente desde')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),
                Section::make('Notas internas')
                    ->headerActions([
                        Action::make('editNotes')
                            ->label('Editar notas')
                            ->icon('heroicon-o-pencil-square')
                            ->modalHeading('Editar notas internas')
                            ->form([
                                Textarea::make('internal_notes')
                                    ->label('Notas internas')
                                    ->rows(8)
                                    ->default(fn($record) => $record->internal_notes)
                                    ->columnSpanFull(),
                            ])
                            ->action(function (array $data, $record): void {
                                $record->update([
                                    'internal_notes' => $data['internal_notes'],
                                ]);
                            }),
                    ])
                    ->schema([
                        TextEntry::make('internal_notes')
                            ->label('')
                            ->placeholder('Sin notas internas')
                            ->columnSpanFull(),
                    ]),
                Section::make('Información adicional')
                    ->schema([
                        TextEntry::make('metadata')
                            ->label('Datos extra')
                            ->placeholder('Sin datos adicionales')
                            ->columnSpanFull(),
                    ]),


            ]);
    }

}
