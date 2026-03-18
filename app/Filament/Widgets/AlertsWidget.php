<?php

namespace App\Filament\Widgets;

use App\Models\FraudReport;
use App\Models\Review;
use App\Models\Residence;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class AlertsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Alertes & Signalements';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FraudReport::query()
                    ->with(['reporter:id,name', 'targetUser:id,name'])
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('fraud_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scam' => 'Arnaque',
                        'fake_listing' => 'Annonce fausse',
                        'harassment' => 'Harcèlement',
                        'inappropriate_content' => 'Contenu inapproprié',
                        'payment_fraud' => 'Fraude paiement',
                        'other' => 'Autre',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'scam', 'payment_fraud' => 'danger',
                        'fake_listing' => 'warning',
                        'harassment' => 'danger',
                        'inappropriate_content' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('Signalé par')
                    ->limit(20),
                Tables\Columns\TextColumn::make('targetUser.name')
                    ->label('Utilisateur signalé')
                    ->limit(20),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn (FraudReport $record): string => route('filament.admin.resources.fraud-reports.edit', $record))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Aucune alerte')
            ->emptyStateDescription('Aucun signalement en attente')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return FraudReport::where('status', 'pending')->exists();
    }
}
