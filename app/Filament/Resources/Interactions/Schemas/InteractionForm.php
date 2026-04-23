<?php

namespace App\Filament\Resources\Interactions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

use Filament\Forms\Components\Textarea;

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
                    ->label('Origen (Web)')
                    ->relationship('source', 'name')
                    ->required(),
                Select::make('service_id')
                    ->label('Servicio Solicitado')
                    ->relationship('service', 'name'),
                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'nuevo' => 'Nuevo',
                        'contactado' => 'Contactado',
                        'vendido' => 'Vendido',
                        'descartado' => 'Descartado',
                    ])
                    ->default('nuevo')
                    ->required(),
                Textarea::make('notes')
                    ->label('Notas internas'),
            ]);
    }
}
