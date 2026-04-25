<?php

namespace App\Filament\Resources\ResidenceVerificationResource\Pages;

use App\Filament\Resources\ResidenceVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResidenceVerifications extends ListRecords
{
    protected static string $resource = ResidenceVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
