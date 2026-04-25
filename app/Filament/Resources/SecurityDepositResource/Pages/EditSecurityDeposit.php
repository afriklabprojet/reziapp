<?php

namespace App\Filament\Resources\SecurityDepositResource\Pages;

use App\Filament\Resources\SecurityDepositResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSecurityDeposit extends EditRecord
{
    protected static string $resource = SecurityDepositResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
