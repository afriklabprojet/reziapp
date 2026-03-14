<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentBookingsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Dernières réservations';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->with(['user', 'residence'])
                    ->latest()
                    ->limit(5),
            )
            ->columns([
                Tables\Columns\TextColumn::make('residence.name')
                    ->label('Résidence')
                    ->limit(25),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client'),
                Tables\Columns\TextColumn::make('check_in')
                    ->label('Arrivée')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('check_out')
                    ->label('Départ')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('XOF'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'En attente',
                        'confirmed' => 'Confirmée',
                        'cancelled' => 'Annulée',
                        'completed' => 'Terminée',
                        default => $state,
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.bookings.edit', $record)),
            ])
            ->paginated(false);
    }
}
