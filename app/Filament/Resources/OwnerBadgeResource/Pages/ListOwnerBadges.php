<?php

namespace App\Filament\Resources\OwnerBadgeResource\Pages;

use App\Filament\Resources\OwnerBadgeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOwnerBadges extends ListRecords
{
    protected static string $resource = OwnerBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
