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
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approuver cette résidence ?')
                    ->modalDescription(fn ($record) => "Valider \"" . $record->name . "\" et la rendre visible aux locataires.")
                    ->action(function ($record) {
                        $record->update(['status' => 'active']);
                        \Filament\Notifications\Notification::make()
                            ->title('Résidence approuvée')
                            ->body($record->name . ' est maintenant active.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rejeter cette résidence ?')
                    ->modalDescription(fn ($record) => "Rejeter \"" . $record->name . "\" et notifier le propriétaire.")
                    ->action(function ($record) {
                        $record->update(['status' => 'rejected']);
                        \Filament\Notifications\Notification::make()
                            ->title('Résidence rejetée')
                            ->body($record->name . ' a été rejetée.')
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn ($record) => route('filament.admin.resources.residences.edit', $record)),
            ])
            ->paginated(false);
    }
}
