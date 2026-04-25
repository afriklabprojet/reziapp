<?php

namespace App\Filament\Resources\RentReminderResource\Pages;

use App\Filament\Resources\RentReminderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRentReminder extends EditRecord
{
    protected static string $resource = RentReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
