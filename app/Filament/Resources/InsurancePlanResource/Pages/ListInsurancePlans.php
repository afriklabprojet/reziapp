<?php

namespace App\Filament\Resources\InsurancePlanResource\Pages;

use App\Filament\Resources\InsurancePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInsurancePlans extends ListRecords
{
    protected static string $resource = InsurancePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
