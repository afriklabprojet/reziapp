<?php

namespace App\Filament\Resources\DigitalCheckinResource\Pages;

use App\Filament\Resources\DigitalCheckinResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDigitalCheckin extends EditRecord
{
    protected static string $resource = DigitalCheckinResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
