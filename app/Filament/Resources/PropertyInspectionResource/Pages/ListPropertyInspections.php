<?php

namespace App\Filament\Resources\PropertyInspectionResource\Pages;

use App\Filament\Resources\PropertyInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPropertyInspections extends ListRecords
{
    protected static string $resource = PropertyInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
