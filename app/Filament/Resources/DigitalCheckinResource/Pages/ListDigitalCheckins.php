<?php

namespace App\Filament\Resources\DigitalCheckinResource\Pages;

use App\Filament\Resources\DigitalCheckinResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDigitalCheckins extends ListRecords
{
    protected static string $resource = DigitalCheckinResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
