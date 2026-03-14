<?php

namespace App\Filament\Resources\FraudReportResource\Pages;

use App\Filament\Resources\FraudReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFraudReports extends ListRecords
{
    protected static string $resource = FraudReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
