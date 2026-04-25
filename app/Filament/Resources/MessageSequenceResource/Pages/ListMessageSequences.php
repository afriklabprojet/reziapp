<?php

namespace App\Filament\Resources\MessageSequenceResource\Pages;

use App\Filament\Resources\MessageSequenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMessageSequences extends ListRecords
{
    protected static string $resource = MessageSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
