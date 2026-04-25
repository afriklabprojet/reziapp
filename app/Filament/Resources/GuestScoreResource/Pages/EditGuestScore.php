<?php

namespace App\Filament\Resources\GuestScoreResource\Pages;

use App\Filament\Resources\GuestScoreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuestScore extends EditRecord
{
    protected static string $resource = GuestScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
