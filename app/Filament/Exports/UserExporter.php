<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Nom'),
            ExportColumn::make('email')
                ->label('Email'),
            ExportColumn::make('phone')
                ->label('Téléphone'),
            ExportColumn::make('role')
                ->label('Rôle'),
            ExportColumn::make('email_verified')
                ->label('Email vérifié'),
            ExportColumn::make('phone_verified')
                ->label('Tél. vérifié'),
            ExportColumn::make('identity_verified')
                ->label('Identité vérifiée'),
            ExportColumn::make('verification_level')
                ->label('Niveau vérification'),
            ExportColumn::make('is_suspended')
                ->label('Suspendu'),
            ExportColumn::make('residences_count')
                ->label('Nb résidences')
                ->counts('residences'),
            ExportColumn::make('created_at')
                ->label('Inscrit le'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return "L'export de {$count} utilisateurs est terminé.";
    }
}
