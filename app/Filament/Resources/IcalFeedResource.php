<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IcalFeedResource\Pages;
use App\Models\IcalFeed;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IcalFeedResource extends Resource
{
    protected static ?string $model = IcalFeed::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $modelLabel = 'Flux iCal';

    protected static ?string $pluralModelLabel = 'Flux iCal';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Flux iCal')
                    ->schema([
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Propriétaire')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('platform')
                            ->label('Plateforme')
                            ->options(IcalFeed::PLATFORMS)
                            ->required(),
                        Forms\Components\TextInput::make('import_url')
                            ->label('URL d\'import')
                            ->url()
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Synchronisation')
                    ->schema([
                        Forms\Components\Toggle::make('auto_sync')
                            ->label('Synchro automatique')
                            ->default(true),
                        Forms\Components\TextInput::make('sync_interval_minutes')
                            ->label('Intervalle (min)')
                            ->numeric()
                            ->default(60),
                        Forms\Components\TextInput::make('sync_status')
                            ->label('Statut')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('imported_events_count')
                            ->label('Événements importés')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('residence.title')
                    ->badge()
                    ->label('Résidence')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->badge()
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('platform')
                    ->badge()
                    ->label('Plateforme')
                    ->formatStateUsing(fn (string $state) => IcalFeed::PLATFORMS[$state] ?? $state),
                Tables\Columns\TextColumn::make('sync_status')
                    ->badge()
                    ->label('Synchro')
                    ->colors([
                        'success' => IcalFeed::SYNC_STATUS_SYNCED,
                        'warning' => IcalFeed::SYNC_STATUS_PENDING,
                        'info'    => IcalFeed::SYNC_STATUS_SYNCING,
                        'danger'  => IcalFeed::SYNC_STATUS_ERROR,
                    ]),
                Tables\Columns\TextColumn::make('imported_events_count')
                    ->badge()
                    ->label('Événements')
                    ->sortable(),
                Tables\Columns\IconColumn::make('auto_sync')
                    ->label('Auto')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_synced_at')
                    ->badge()
                    ->label('Dernière synchro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Jamais'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->label('Plateforme')
                    ->options(IcalFeed::PLATFORMS),
                Tables\Filters\SelectFilter::make('sync_status')
                    ->label('Statut synchro')
                    ->options([
                        IcalFeed::SYNC_STATUS_PENDING => 'En attente',
                        IcalFeed::SYNC_STATUS_SYNCING => 'En cours',
                        IcalFeed::SYNC_STATUS_SYNCED  => 'Synchronisé',
                        IcalFeed::SYNC_STATUS_ERROR   => 'Erreur',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListIcalFeeds::route('/'),
            'create' => Pages\CreateIcalFeed::route('/create'),
            'edit'   => Pages\EditIcalFeed::route('/{record}/edit'),
        ];
    }
}
