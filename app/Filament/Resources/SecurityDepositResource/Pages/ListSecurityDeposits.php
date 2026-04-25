<?php

namespace App\Filament\Resources\SecurityDepositResource\Pages;

use App\Filament\Resources\SecurityDepositResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSecurityDeposits extends ListRecords
{
    protected static string $resource = SecurityDepositResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
