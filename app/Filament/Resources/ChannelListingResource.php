<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChannelListingResource\Pages;
use App\Models\ChannelListing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChannelListingResource extends Resource
{
    protected static ?string $model = ChannelListing::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $modelLabel = 'Canal de distribution';

    protected static ?string $pluralModelLabel = 'Canaux de distribution';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Canal')
                    ->schema([
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('channel')
                            ->label('Canal')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('external_id')
                            ->label('ID externe')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])->columns(2),
                Forms\Components\Section::make('Synchronisation')
                    ->schema([
                        Forms\Components\TextInput::make('sync_status')
                            ->label('Statut synchro')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('sync_message')
                            ->label('Message')
                            ->rows(2)
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
                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->label('Canal')
                    ->badge(),
                Tables\Columns\TextColumn::make('external_id')
                    ->badge()
                    ->label('ID externe')
                    ->limit(30)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('sync_status')
                    ->badge()
                    ->label('Statut synchro')
                    ->colors([
                        'success' => 'synced',
                        'warning' => 'pending',
                        'info'    => 'syncing',
                        'danger'  => 'error',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_sync_at')
                    ->badge()
                    ->label('Dernière synchro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Jamais'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->label('Canal')
                    ->options(fn () => ChannelListing::distinct()->pluck('channel', 'channel')->toArray()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
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
            'index'  => Pages\ListChannelListings::route('/'),
            'create' => Pages\CreateChannelListing::route('/create'),
            'edit'   => Pages\EditChannelListing::route('/{record}/edit'),
        ];
    }
}
