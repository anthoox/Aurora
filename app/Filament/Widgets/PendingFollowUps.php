<?php

// namespace App\Filament\Widgets;

// use App\Models\Interaction;
// use Filament\Actions\Action;
// use Filament\Tables\Columns\TextColumn;
// use Filament\Tables\Table;
// use Filament\Widgets\TableWidget;

// class PendingFollowUps extends TableWidget
// {
//     protected static ?int $sort = 4;

//     protected int|string|array $columnSpan = 2;

//     public function table(Table $table): Table
//     {
//         return $table
//             ->query(
//                 Interaction::query()
//                     ->whereIn('status', ['nuevo', 'contactado'])
//                     ->whereDoesntHave('bookings')
//                     ->where('updated_at', '<=', now()->subHours(24))
//                     ->latest('updated_at')
//                     ->limit(5)
//             )
//             ->columns([
//                 TextColumn::make('customer.email')
//                     ->label('Cliente'),

//                 TextColumn::make('source.name')
//                     ->label('Origen')
//                     ->badge(),

//                 TextColumn::make('service.name')
//                     ->label('Servicio')
//                     ->placeholder('Sin servicio'),

//                 TextColumn::make('updated_at')
//                     ->label('Última actividad')
//                     ->since(),

//                 TextColumn::make('status')
//                     ->label('Estado')
//                     ->badge(),
//             ])
//             ->actions([
//                 Action::make('view')
//                     ->label('Ver')
//                     ->icon('heroicon-m-eye')
//                     ->url(fn(Interaction $record): string => "/admin/interactions/{$record->getKey()}"),
//             ]);
//     }
// }