<?php

namespace App\Filament\Resources\TenantReviewResource\Pages;

use App\Filament\Resources\TenantReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenantReview extends EditRecord
{
    protected static string $resource = TenantReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
