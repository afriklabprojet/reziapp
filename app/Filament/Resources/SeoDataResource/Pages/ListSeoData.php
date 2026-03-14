<?php

namespace App\Filament\Resources\SeoDataResource\Pages;

use App\Filament\Resources\SeoDataResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeoData extends ListRecords
{
    protected static string $resource = SeoDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
