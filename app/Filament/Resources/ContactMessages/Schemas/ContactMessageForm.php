<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gestión del contacto')
                    ->schema([
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'nuevo' => 'Nuevo',
                                'respondido' => 'Respondido',
                                'convertido' => 'Convertido',
                                'archivado' => 'Archivado',
                            ])
                            ->required(),
                    ]),
            ]);
    }
}