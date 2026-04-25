<?php

namespace App\Filament\Resources\AutoReplyResource\Pages;

use App\Filament\Resources\AutoReplyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAutoReplies extends ListRecords
{
    protected static string $resource = AutoReplyResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
