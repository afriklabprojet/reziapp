<?php

namespace App\Filament\Resources\IcalFeedResource\Pages;

use App\Filament\Resources\IcalFeedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIcalFeed extends EditRecord
{
    protected static string $resource = IcalFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
