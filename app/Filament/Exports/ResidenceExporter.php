<?php

namespace App\Filament\Exports;

use App\Models\Residence;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ResidenceExporter extends Exporter
{
    protected static ?string $model = Residence::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Nom'),
            ExportColumn::make('owner.name')
                ->label('Propriétaire'),
            ExportColumn::make('commune')
                ->label('Commune'),
            ExportColumn::make('quartier')
                ->label('Quartier'),
            ExportColumn::make('type')
                ->label('Type'),
            ExportColumn::make('price_per_day')
                ->label('Prix/jour (FCFA)'),
            ExportColumn::make('price_per_week')
                ->label('Prix/semaine (FCFA)'),
            ExportColumn::make('price_per_month')
                ->label('Prix/mois (FCFA)'),
            ExportColumn::make('status')
                ->label('Statut'),
            ExportColumn::make('bedrooms')
                ->label('Chambres'),
            ExportColumn::make('bathrooms')
                ->label('Salles de bain'),
            ExportColumn::make('max_guests')
                ->label('Voyageurs max'),
            ExportColumn::make('views_count')
                ->label('Vues'),
            ExportColumn::make('average_rating')
                ->label('Note moyenne'),
            ExportColumn::make('is_verified')
                ->label('Vérifiée'),
            ExportColumn::make('created_at')
                ->label('Créée le'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return "L'export de {$count} résidences est terminé.";
    }
}
