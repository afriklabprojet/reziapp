<?php

namespace App\Filament\Resources\OwnerBalanceResource\Pages;

use App\Filament\Resources\OwnerBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOwnerBalance extends EditRecord
{
    protected static string $resource = OwnerBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
