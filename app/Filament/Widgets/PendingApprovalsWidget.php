<?php

namespace App\Filament\Widgets;

use App\Models\Residence;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingApprovalsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Résidences en attente d\'approbation';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Residence::query()
                    ->with('owner')
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(5),
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->limit(30),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire'),
                Tables\Columns\TextColumn::make('commune')
                    ->label('Commune'),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('country_code')
                    ->label('Pays')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'CI' => '🇨🇮 CI',
                        'BF' => '🇧🇫 BF',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('price_per_day')
                    ->label('Prix/jour')
                    ->money('XOF'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Soumise le')
                    ->date('d/m/Y'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn ($record) => $record->update(['status' => 'active'])),
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.residences.edit', $record)),
            ])
            ->paginated(false);
    }
}
