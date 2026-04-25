<?php

namespace App\Filament\Resources\GuestScoreResource\Pages;

use App\Filament\Resources\GuestScoreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuestScores extends ListRecords
{
    protected static string $resource = GuestScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
