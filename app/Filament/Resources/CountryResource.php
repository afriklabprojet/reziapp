<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CountryResource\Pages;
use App\Models\Country;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Paramètres';

    protected static ?string $navigationLabel = 'Pays';

    protected static ?string $modelLabel = 'Pays';

    protected static ?string $pluralModelLabel = 'Pays';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code ISO')
                            ->required()
                            ->maxLength(2)
                            ->unique(ignoreRecord: true)
                            ->placeholder('CI'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Côte d\'Ivoire'),

                        Forms\Components\TextInput::make('flag_emoji')
                            ->label('Drapeau (Emoji)')
                            ->maxLength(10)
                            ->placeholder('🇨🇮'),

                        Forms\Components\TextInput::make('phone_code')
                            ->label('Indicatif téléphonique')
                            ->required()
                            ->maxLength(5)
                            ->placeholder('+225'),
                    ])->columns(2),

                Forms\Components\Section::make('Devise')
                    ->schema([
                        Forms\Components\TextInput::make('currency')
                            ->label('Code devise')
                            ->required()
                            ->maxLength(5)
                            ->placeholder('XOF'),

                        Forms\Components\TextInput::make('currency_symbol')
                            ->label('Symbole')
                            ->required()
                            ->maxLength(10)
                            ->placeholder('FCFA'),

                        Forms\Components\TextInput::make('currency_name')
                            ->label('Nom de la devise')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Franc CFA'),
                    ])->columns(3),

                Forms\Components\Section::make('Localisation')
                    ->schema([
                        Forms\Components\TextInput::make('locale')
                            ->label('Locale')
                            ->required()
                            ->maxLength(10)
                            ->placeholder('fr_CI'),

                        Forms\Components\TextInput::make('timezone')
                            ->label('Fuseau horaire')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Africa/Abidjan'),
                    ])->columns(2),

                Forms\Components\Section::make('Coordonnées géographiques')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude centrale')
                            ->required()
                            ->numeric(),

                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude centrale')
                            ->required()
                            ->numeric(),

                        Forms\Components\TextInput::make('min_lat')
                            ->label('Latitude min')
                            ->required()
                            ->numeric(),

                        Forms\Components\TextInput::make('max_lat')
                            ->label('Latitude max')
                            ->required()
                            ->numeric(),

                        Forms\Components\TextInput::make('min_lng')
                            ->label('Longitude min')
                            ->required()
                            ->numeric(),

                        Forms\Components\TextInput::make('max_lng')
                            ->label('Longitude max')
                            ->required()
                            ->numeric(),
                    ])->columns(3),

                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('flag_emoji')
                    ->label('')
                    ->searchable(false),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone_code')
                    ->label('Indicatif')
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency_symbol')
                    ->label('Devise')
                    ->sortable(),

                Tables\Columns\TextColumn::make('timezone')
                    ->label('Fuseau')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'edit' => Pages\EditCountry::route('/{record}/edit'),
        ];
    }
}
