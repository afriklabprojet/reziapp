<?php

namespace App\Filament\Resources\IcalFeedResource\Pages;

use App\Filament\Resources\IcalFeedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIcalFeeds extends ListRecords
{
    protected static string $resource = IcalFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
