<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PointOfInterestResource\Pages;
use App\Models\PointOfInterest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PointOfInterestResource extends Resource
{
    protected static ?string $model = PointOfInterest::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $modelLabel = 'Point d\'intérêt';

    protected static ?string $pluralModelLabel = 'Points d\'intérêt';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Localisation')
                    ->schema([
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(array_combine(
                                array_keys(PointOfInterest::TYPES),
                                array_column(PointOfInterest::TYPES, 'label'),
                            ))
                            ->required(),
                        Forms\Components\TextInput::make('distance_meters')
                            ->label('Distance (m)')
                            ->numeric(),
                        Forms\Components\TextInput::make('walking_time_minutes')
                            ->label('Temps de marche (min)')
                            ->numeric(),
                    ])->columns(2),
                Forms\Components\Section::make('Coordonnées GPS')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric(),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('residence.title')
                    ->label('Résidence')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state) => PointOfInterest::TYPES[$state]['label'] ?? $state),
                Tables\Columns\TextColumn::make('distance_meters')
                    ->label('Distance')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} m" : '—'),
                Tables\Columns\TextColumn::make('walking_time_minutes')
                    ->label('À pied')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} min" : '—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(array_combine(
                        array_keys(PointOfInterest::TYPES),
                        array_column(PointOfInterest::TYPES, 'label'),
                    )),
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
            'index'  => Pages\ListPointOfInterests::route('/'),
            'create' => Pages\CreatePointOfInterest::route('/create'),
            'edit'   => Pages\EditPointOfInterest::route('/{record}/edit'),
        ];
    }
}
