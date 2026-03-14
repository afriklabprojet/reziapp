<?php

namespace App\Filament\Resources\IdentityVerificationResource\Pages;

use App\Filament\Resources\IdentityVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIdentityVerifications extends ListRecords
{
    protected static string $resource = IdentityVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
