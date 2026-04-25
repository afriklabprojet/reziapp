<?php

namespace App\Filament\Resources\RentReceiptResource\Pages;

use App\Filament\Resources\RentReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRentReceipt extends EditRecord
{
    protected static string $resource = RentReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
