<?php

namespace App\Filament\Resources\CommuneListResource\Pages;

use App\Filament\Resources\CommuneListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommuneLists extends ListRecords
{
    protected static string $resource = CommuneListResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
