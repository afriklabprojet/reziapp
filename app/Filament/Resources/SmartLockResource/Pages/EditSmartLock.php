<?php

namespace App\Filament\Resources\SmartLockResource\Pages;

use App\Filament\Resources\SmartLockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSmartLock extends EditRecord
{
    protected static string $resource = SmartLockResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
