<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Exports\BookingExporter;
use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(BookingExporter::class)
                ->label('Exporter')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make(),
        ];
    }
}
