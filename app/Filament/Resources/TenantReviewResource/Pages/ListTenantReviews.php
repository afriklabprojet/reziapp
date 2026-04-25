<?php

namespace App\Filament\Resources\TenantReviewResource\Pages;

use App\Filament\Resources\TenantReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenantReviews extends ListRecords
{
    protected static string $resource = TenantReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
