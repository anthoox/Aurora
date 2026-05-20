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
use App\Filament\Resources\Customers\RelationManagers\InteractionsRelationManager;
use App\Filament\Resources\Customers\RelationManagers\BookingHistoryRelationManager;
use Filament\Schemas\Components\Grid;
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
            InteractionsRelationManager::class,
            BookingsRelationManager::class,
            BookingHistoryRelationManager::class,
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
                Grid::make([
                    'lg' => 2,
                ])->columnSpanFull()
                    ->schema([

                        // COLUMNA IZQUIERDA
                        Grid::make(1)
                            ->schema([

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
                            ]),

                        // COLUMNA DERECHA
                        Grid::make(1)
                            ->schema([

                                Section::make('Resumen comercial')
                                    ->schema([
                                        TextEntry::make('total_leads')
                                            ->label('Leads')
                                            ->state(fn($record) => $record->interactions()->count())
                                            ->badge()
                                            ->color('info'),

                                        TextEntry::make('total_bookings')
                                            ->label('Reservas')
                                            ->state(fn($record) => $record->bookings()->count())
                                            ->badge()
                                            ->color('primary'),

                                        TextEntry::make('completed_bookings')
                                            ->label('Realizadas')
                                            ->state(fn($record) => $record->bookings()
                                                ->where('status', 'realizada')
                                                ->count())
                                            ->badge()
                                            ->color('success'),

                                        TextEntry::make('cancelled_bookings')
                                            ->label('Canceladas')
                                            ->state(fn($record) => $record->bookings()
                                                ->where('status', 'cancelada')
                                                ->count())
                                            ->badge()
                                            ->color('danger'),
                                    ])
                                    ->columns(4),

                                Section::make('Información adicional')
                                    ->schema([
                                        TextEntry::make('metadata')
                                            ->label('Datos extra')
                                            ->placeholder('Sin datos adicionales')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    

}
