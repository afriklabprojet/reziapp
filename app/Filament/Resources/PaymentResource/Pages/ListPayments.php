<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Exports\PaymentExporter;
use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(PaymentExporter::class)
                ->label('Exporter')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make(),
        ];
    }
}
