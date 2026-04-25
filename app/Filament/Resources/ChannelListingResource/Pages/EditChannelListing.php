<?php

namespace App\Filament\Resources\ChannelListingResource\Pages;

use App\Filament\Resources\ChannelListingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChannelListing extends EditRecord
{
    protected static string $resource = ChannelListingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
