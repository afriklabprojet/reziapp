<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Paiements récents';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->with(['user', 'booking.residence', 'paymentMethod'])
                    ->latest()
                    ->limit(5),
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-clipboard-document'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable(),

                Tables\Columns\TextColumn::make('booking.residence.name')
                    ->label('Résidence')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->booking?->residence?->name),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paymentMethod.type')
                    ->label('Méthode')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'card' => '💳 Carte',
                        'mobile_money' => '📱 Mobile Money',
                        'orange_money' => '🟠 Orange Money',
                        'mtn_money' => '🟡 MTN Money',
                        'wave' => '🌊 Wave',
                        'bank_transfer' => '🏦 Virement',
                        'cash' => '💵 Espèces',
                        default => $state ?? 'Non défini',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        'partial_refund' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match($state) {
                        'completed' => 'heroicon-m-check-circle',
                        'pending' => 'heroicon-m-clock',
                        'failed' => 'heroicon-m-x-circle',
                        'refunded' => 'heroicon-m-arrow-uturn-left',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'completed' => 'Complété',
                        'pending' => 'En attente',
                        'failed' => 'Échoué',
                        'refunded' => 'Remboursé',
                        'partial_refund' => 'Partiel',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.payments.view', $record)),
            ])
            ->paginated(false);
    }

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->role === 'admin';
    }
}
