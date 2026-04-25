<?php

namespace App\Filament\Resources\InsurancePlanResource\Pages;

use App\Filament\Resources\InsurancePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInsurancePlan extends EditRecord
{
    protected static string $resource = InsurancePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
