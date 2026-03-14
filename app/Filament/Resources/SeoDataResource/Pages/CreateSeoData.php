<?php

namespace App\Filament\Resources\SeoDataResource\Pages;

use App\Filament\Resources\SeoDataResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSeoData extends CreateRecord
{
    protected static string $resource = SeoDataResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
