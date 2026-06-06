<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use App\Filament\Resources\CampaignResource\Support\CampaignActionFactory;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CampaignActionFactory::makePageSendAction($this->record),
            CampaignActionFactory::makePageTestAction($this->record),
            Actions\Action::make('preview')
                ->label('Aperçu')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalHeading('Aperçu de la campagne')
                ->modalContent(fn () => view('filament.pages.campaign-preview', ['campaign' => $this->record]))
                ->modalSubmitAction(false),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
