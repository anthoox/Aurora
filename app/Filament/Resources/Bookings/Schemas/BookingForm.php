<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('customer_id')
                    ->required()
                    ->numeric(),
                TextInput::make('interaction_id')
                    ->numeric(),
                TextInput::make('service_id')
                    ->numeric(),
                TextInput::make('source_id')
                    ->numeric(),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('ends_at'),
                TextInput::make('status')
                    ->required()
                    ->default('pendiente'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
