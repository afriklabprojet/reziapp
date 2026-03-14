<?php

namespace App\Filament\Resources\SponsoredListingResource\Pages;

use App\Filament\Resources\SponsoredListingResource;
use App\Filament\Widgets\SponsoredStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSponsoredListings extends ListRecords
{
    protected static string $resource = SponsoredListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SponsoredStatsWidget::class,
        ];
    }
}
