<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Support\CampaignResourceUi;
use App\Filament\Resources\CampaignResource\Pages;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Campagnes';

    protected static ?string $modelLabel = 'Campagne';

    protected static ?string $pluralModelLabel = 'Campagnes';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Campaign')
                    ->tabs(CampaignResourceUi::formTabs())
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(CampaignResourceUi::tableColumns())
            ->defaultSort('created_at', 'desc')
            ->filters(CampaignResourceUi::tableFilters())
            ->actions(CampaignResourceUi::tableActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $draft = static::getModel()::where('status', 'draft')->count();
        $scheduled = static::getModel()::where('status', 'scheduled')->count();
        $total = $draft + $scheduled;

        return $total ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $scheduled = static::getModel()::where('status', 'scheduled')->count();

        return $scheduled > 0 ? 'warning' : 'gray';
    }
}
