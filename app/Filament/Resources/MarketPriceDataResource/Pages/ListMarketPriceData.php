<?php

namespace App\Filament\Resources\MarketPriceDataResource\Pages;

use App\Filament\Resources\MarketPriceDataResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarketPriceData extends ListRecords
{
    protected static string $resource = MarketPriceDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
