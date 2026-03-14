<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvelle campagne'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes')
                ->badge(fn () => $this->getModel()::count()),
            'draft' => Tab::make('Brouillons')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(fn () => $this->getModel()::where('status', 'draft')->count())
                ->badgeColor('gray'),
            'scheduled' => Tab::make('Planifiées')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'scheduled'))
                ->badge(fn () => $this->getModel()::where('status', 'scheduled')->count())
                ->badgeColor('warning'),
            'sent' => Tab::make('Envoyées')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sent'))
                ->badge(fn () => $this->getModel()::where('status', 'sent')->count())
                ->badgeColor('success'),
            'failed' => Tab::make('Échouées')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge(fn () => $this->getModel()::where('status', 'failed')->count())
                ->badgeColor('danger'),
        ];
    }
}
