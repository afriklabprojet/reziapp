<?php

namespace App\Filament\Resources\ResidenceVerificationResource\Pages;

use App\Filament\Resources\ResidenceVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResidenceVerification extends EditRecord
{
    protected static string $resource = ResidenceVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
