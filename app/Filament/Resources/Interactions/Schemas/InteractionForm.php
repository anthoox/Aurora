<?php

namespace App\Filament\Resources\Interactions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use App\Models\Service;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
class InteractionForm
{
    // app/Filament/Resources/Interactions/Schemas/InteractionForm.php

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('Cliente')
                    ->relationship('customer', 'email') // Muestra el email para identificarlo
                    ->searchable()
                    ->required(),
                Select::make('source_id')
                    ->label('Origen')
                    ->relationship('source', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn($set) => $set('service_id', null)),
                Select::make('service_id')
                    ->label('Servicio')
                    ->options(function ($get) {
                        $sourceId = $get('source_id');

                        if (!$sourceId) {
                            return [];
                        }

                        return Service::query()
                            ->whereHas('sources', function ($query) use ($sourceId) {
                                $query
                                    ->where('sources.id', $sourceId)
                                    ->where('service_source.is_active', true);
                            })
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('Selecciona primero un origen'),
                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'nuevo' => 'Nuevo',
                        'contactado' => 'Contactado',
                        'reservado' => 'Reservado',
                        'vendido' => 'Vendido',
                        'descartado' => 'Descartado',
                    ])
                    ->default('nuevo')
                    ->required(),
                Textarea::make('notes')
                    ->label('Notas internas'),
                Hidden::make('origin_type')
                    ->default('manual'),
            ]);
    }
}
