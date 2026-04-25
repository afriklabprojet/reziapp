<?php

namespace App\Filament\Resources\RentReminderResource\Pages;

use App\Filament\Resources\RentReminderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRentReminders extends ListRecords
{
    protected static string $resource = RentReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
