<?php

namespace App\Filament\Resources\CommuneListResource\Pages;

use App\Filament\Resources\CommuneListResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommuneList extends EditRecord
{
    protected static string $resource = CommuneListResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
