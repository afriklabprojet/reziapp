<?php

namespace App\Filament\Resources\SponsoredListingResource\Pages;

use App\Filament\Resources\SponsoredListingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSponsoredListing extends EditRecord
{
    protected static string $resource = SponsoredListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
