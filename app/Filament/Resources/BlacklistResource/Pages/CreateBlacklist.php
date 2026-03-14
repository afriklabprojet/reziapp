<?php

namespace App\Filament\Resources\BlacklistResource\Pages;

use App\Filament\Resources\BlacklistResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBlacklist extends CreateRecord
{
    protected static string $resource = BlacklistResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
