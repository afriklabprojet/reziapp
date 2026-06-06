<?php

namespace App\Filament\Resources\PaymentProviderResource\Pages;

use App\Filament\Resources\PaymentProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentProvider extends EditRecord
{
    protected static string $resource = PaymentProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
