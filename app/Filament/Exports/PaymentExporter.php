<?php

namespace App\Filament\Exports;

use App\Models\Payment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PaymentExporter extends Exporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('provider_transaction_id')
                ->label('Transaction ID'),
            ExportColumn::make('booking.reference')
                ->label('N° Réservation'),
            ExportColumn::make('user.name')
                ->label('Payeur'),
            ExportColumn::make('user.email')
                ->label('Email'),
            ExportColumn::make('amount')
                ->label('Montant'),
            ExportColumn::make('currency')
                ->label('Devise'),
            ExportColumn::make('paymentMethod.type')
                ->label('Méthode'),
            ExportColumn::make('provider.name')
                ->label('Provider'),
            ExportColumn::make('provider_reference')
                ->label('Ref Provider'),
            ExportColumn::make('status')
                ->label('Statut')
                ->formatStateUsing(fn ($state) => match($state) {
                    'pending' => 'En attente',
                    'completed' => 'Complété',
                    'failed' => 'Échoué',
                    'refunded' => 'Remboursé',
                    'cancelled' => 'Annulé',
                    default => $state,
                }),
            ExportColumn::make('completed_at')
                ->label('Payé le'),
            ExportColumn::make('created_at')
                ->label('Créé le'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'L\'export de '.number_format($export->successful_rows).' paiement(s) est terminé !';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' ligne(s) ont échoué.';
        }

        return $body;
    }
}
