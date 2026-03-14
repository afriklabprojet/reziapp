<?php

namespace App\Filament\Resources\MarketPriceDataResource\Pages;

use App\Filament\Resources\MarketPriceDataResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketPriceData extends EditRecord
{
    protected static string $resource = MarketPriceDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
