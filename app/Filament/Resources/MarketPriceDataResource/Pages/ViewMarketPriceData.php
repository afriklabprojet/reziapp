<?php

namespace App\Filament\Resources\MarketPriceDataResource\Pages;

use App\Filament\Resources\MarketPriceDataResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMarketPriceData extends ViewRecord
{
    protected static string $resource = MarketPriceDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
