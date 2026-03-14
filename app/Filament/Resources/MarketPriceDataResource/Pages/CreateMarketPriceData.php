<?php

namespace App\Filament\Resources\MarketPriceDataResource\Pages;

use App\Filament\Resources\MarketPriceDataResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMarketPriceData extends CreateRecord
{
    protected static string $resource = MarketPriceDataResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
