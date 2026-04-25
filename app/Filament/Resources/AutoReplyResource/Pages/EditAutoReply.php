<?php

namespace App\Filament\Resources\AutoReplyResource\Pages;

use App\Filament\Resources\AutoReplyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAutoReply extends EditRecord
{
    protected static string $resource = AutoReplyResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
