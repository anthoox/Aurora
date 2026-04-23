<?php

namespace App\Filament\Resources\Sources\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
class SourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                Select::make('services')
                    ->multiple() // Permite elegir varios servicios
                    ->relationship('services', 'name') // 'services' es el nombre del método en el modelo Source
                    ->preload() // Carga los servicios al abrir el selector para que sea más rápido
                    ->searchable() // Permite buscar servicios si la lista crece mucho
            ]);
    }
}
