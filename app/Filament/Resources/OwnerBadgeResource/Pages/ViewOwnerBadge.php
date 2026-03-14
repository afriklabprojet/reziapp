<?php

namespace App\Filament\Resources\OwnerBadgeResource\Pages;

use App\Filament\Resources\OwnerBadgeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOwnerBadge extends ViewRecord
{
    protected static string $resource = OwnerBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
