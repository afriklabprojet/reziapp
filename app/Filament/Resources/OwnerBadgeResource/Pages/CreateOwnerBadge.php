<?php

namespace App\Filament\Resources\OwnerBadgeResource\Pages;

use App\Filament\Resources\OwnerBadgeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOwnerBadge extends CreateRecord
{
    protected static string $resource = OwnerBadgeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['awarded_by'] = auth()->id();

        return $data;
    }
}
