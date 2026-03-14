<?php

namespace App\Filament\Resources\ResidenceResource\Pages;

use App\Filament\Resources\ResidenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResidence extends EditRecord
{
    protected static string $resource = ResidenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
