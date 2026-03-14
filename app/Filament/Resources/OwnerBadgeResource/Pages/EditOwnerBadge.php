<?php

namespace App\Filament\Resources\OwnerBadgeResource\Pages;

use App\Filament\Resources\OwnerBadgeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOwnerBadge extends EditRecord
{
    protected static string $resource = OwnerBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
