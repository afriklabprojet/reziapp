<?php

namespace App\Filament\Resources\OwnerDocumentResource\Pages;

use App\Filament\Resources\OwnerDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOwnerDocument extends EditRecord
{
    protected static string $resource = OwnerDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
