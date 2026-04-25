<?php

namespace App\Filament\Resources\OwnerDocumentResource\Pages;

use App\Filament\Resources\OwnerDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOwnerDocuments extends ListRecords
{
    protected static string $resource = OwnerDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
