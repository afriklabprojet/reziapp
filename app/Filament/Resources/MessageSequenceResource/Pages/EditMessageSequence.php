<?php

namespace App\Filament\Resources\MessageSequenceResource\Pages;

use App\Filament\Resources\MessageSequenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMessageSequence extends EditRecord
{
    protected static string $resource = MessageSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
