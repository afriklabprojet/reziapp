<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmenityResource\Pages;
use App\Models\Amenity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AmenityResource extends Resource
{
    protected static ?string $model = Amenity::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Gestion';

    protected static ?string $navigationLabel = 'Équipements';

    protected static ?string $modelLabel = 'Équipement';

    protected static ?string $pluralModelLabel = 'Équipements';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('icon')
                            ->label('Icône (classe heroicon)')
                            ->placeholder('heroicon-o-wifi')
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'essentials' => 'Essentiels',
                                'bathroom' => 'Salle de bain',
                                'bedroom' => 'Chambre',
                                'kitchen' => 'Cuisine',
                                'entertainment' => 'Divertissement',
                                'outdoor' => 'Extérieur',
                                'safety' => 'Sécurité',
                                'services' => 'Services',
                            ]),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Mis en avant'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('icon')
                    ->label('Icône'),
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'essentials' => 'Essentiels',
                        'bathroom' => 'Salle de bain',
                        'bedroom' => 'Chambre',
                        'kitchen' => 'Cuisine',
                        'entertainment' => 'Divertissement',
                        'outdoor' => 'Extérieur',
                        'safety' => 'Sécurité',
                        'services' => 'Services',
                        default => $state ?? '-',
                    }),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Vedette')
                    ->boolean(),
                Tables\Columns\TextColumn::make('residences_count')
                    ->label('Utilisé')
                    ->counts('residences')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'essentials' => 'Essentiels',
                        'bathroom' => 'Salle de bain',
                        'bedroom' => 'Chambre',
                        'kitchen' => 'Cuisine',
                        'entertainment' => 'Divertissement',
                        'outdoor' => 'Extérieur',
                        'safety' => 'Sécurité',
                        'services' => 'Services',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAmenities::route('/'),
            'create' => Pages\CreateAmenity::route('/create'),
            'edit' => Pages\EditAmenity::route('/{record}/edit'),
        ];
    }
}
