<?php

namespace App\Filament\Resources\ChannelListingResource\Pages;

use App\Filament\Resources\ChannelListingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChannelListings extends ListRecords
{
    protected static string $resource = ChannelListingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
