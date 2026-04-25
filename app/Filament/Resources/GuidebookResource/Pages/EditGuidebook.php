<?php

namespace App\Filament\Resources\GuidebookResource\Pages;

use App\Filament\Resources\GuidebookResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuidebook extends EditRecord
{
    protected static string $resource = GuidebookResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
