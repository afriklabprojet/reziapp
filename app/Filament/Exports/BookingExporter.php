<?php

namespace App\Filament\Exports;

use App\Models\Booking;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class BookingExporter extends Exporter
{
    protected static ?string $model = Booking::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('reference')
                ->label('N° Réservation'),
            ExportColumn::make('user.name')
                ->label('Client'),
            ExportColumn::make('user.email')
                ->label('Email Client'),
            ExportColumn::make('user.phone')
                ->label('Téléphone Client'),
            ExportColumn::make('residence.name')
                ->label('Résidence'),
            ExportColumn::make('residence.owner.name')
                ->label('Propriétaire'),
            ExportColumn::make('check_in')
                ->label('Date Arrivée'),
            ExportColumn::make('check_out')
                ->label('Date Départ'),
            ExportColumn::make('nights')
                ->label('Nuits'),
            ExportColumn::make('guests')
                ->label('Voyageurs'),
            ExportColumn::make('price_per_night')
                ->label('Prix/Nuit'),
            ExportColumn::make('cleaning_fee')
                ->label('Frais Ménage'),
            ExportColumn::make('service_fee')
                ->label('Frais Service'),
            ExportColumn::make('total_amount')
                ->label('Prix Total'),
            ExportColumn::make('status')
                ->label('Statut')
                ->formatStateUsing(fn ($state) => match($state) {
                    'pending' => 'En attente',
                    'confirmed' => 'Confirmée',
                    'cancelled' => 'Annulée',
                    'completed' => 'Terminée',
                    'rejected' => 'Rejetée',
                    default => $state,
                }),
            ExportColumn::make('payment_status')
                ->label('Statut Paiement')
                ->formatStateUsing(fn ($state) => match($state) {
                    'pending' => 'En attente',
                    'paid' => 'Payé',
                    'refunded' => 'Remboursé',
                    'failed' => 'Échoué',
                    default => $state,
                }),
            ExportColumn::make('payment_method')
                ->label('Moyen Paiement'),
            ExportColumn::make('created_at')
                ->label('Créée le'),
            ExportColumn::make('confirmed_at')
                ->label('Confirmée le'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'L\'export de '.number_format($export->successful_rows).' réservation(s) est terminé !';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' ligne(s) ont échoué.';
        }

        return $body;
    }
}
