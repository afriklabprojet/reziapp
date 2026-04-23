<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeoDataResource\Pages;
use App\Models\SeoData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SeoDataResource extends Resource
{
    protected static ?string $model = SeoData::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationGroup = 'Paramètres';

    protected static ?string $navigationLabel = 'SEO par page';

    protected static ?string $modelLabel = 'SEO par page';

    protected static ?string $pluralModelLabel = 'SEO par page';

    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Page cible')
                    ->schema([
                        Forms\Components\TextInput::make('route_name')
                            ->label('Nom de la route')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('residences.show'),

                        Forms\Components\TextInput::make('url_pattern')
                            ->label('Pattern d\'URL')
                            ->placeholder('/residences/{slug}'),

                        Forms\Components\Select::make('page_type')
                            ->label('Type de page')
                            ->options([
                                'home' => 'Page d\'accueil',
                                'listing' => 'Liste',
                                'detail' => 'Détail',
                                'static' => 'Page statique',
                                'search' => 'Recherche',
                                'category' => 'Catégorie',
                            ])
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Méta-données')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre SEO')
                            ->required()
                            ->maxLength(70)
                            ->helperText('Max 70 caractères recommandés')
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                                $set('title_length', strlen($state ?? ''))),

                        Forms\Components\Placeholder::make('title_length')
                            ->label('Longueur du titre')
                            ->content(fn (Forms\Get $get): string =>
                                strlen($get('title') ?? '').'/70 caractères'),

                        Forms\Components\Textarea::make('description')
                            ->label('Méta description')
                            ->required()
                            ->rows(3)
                            ->maxLength(160)
                            ->helperText('Max 160 caractères recommandés'),

                        Forms\Components\TagsInput::make('keywords')
                            ->label('Mots-clés')
                            ->separator(',')
                            ->placeholder('Ajouter un mot-clé'),
                    ]),

                Forms\Components\Section::make('Open Graph (Réseaux sociaux)')
                    ->schema([
                        Forms\Components\TextInput::make('og_title')
                            ->label('Titre OG')
                            ->maxLength(95)
                            ->placeholder('Utilise le titre SEO si vide'),

                        Forms\Components\Textarea::make('og_description')
                            ->label('Description OG')
                            ->rows(2)
                            ->maxLength(200),

                        Forms\Components\FileUpload::make('og_image')
                            ->label('Image OG')
                            ->image()
                            ->directory('seo/og')
                            ->helperText('1200x630px recommandé'),

                        Forms\Components\Select::make('og_type')
                            ->label('Type OG')
                            ->options([
                                'website' => 'Website',
                                'article' => 'Article',
                                'place' => 'Place',
                                'product' => 'Product',
                            ])
                            ->default('website'),
                    ])->columns(2),

                Forms\Components\Section::make('Schema.org (Données structurées)')
                    ->schema([
                        Forms\Components\Select::make('schema_type')
                            ->label('Type de schema')
                            ->options([
                                'WebSite' => 'WebSite',
                                'WebPage' => 'WebPage',
                                'RealEstateListing' => 'RealEstateListing',
                                'Place' => 'Place',
                                'LocalBusiness' => 'LocalBusiness',
                                'FAQPage' => 'FAQPage',
                            ]),

                        Forms\Components\Textarea::make('schema_json')
                            ->label('JSON-LD personnalisé')
                            ->rows(6)
                            ->helperText('Laissez vide pour générer automatiquement'),
                    ])->collapsed(),

                Forms\Components\Section::make('Options')
                    ->schema([
                        Forms\Components\Toggle::make('is_noindex')
                            ->label('NoIndex (ne pas indexer)')
                            ->helperText('Empêche les moteurs de recherche d\'indexer cette page'),

                        Forms\Components\Toggle::make('is_nofollow')
                            ->label('NoFollow')
                            ->helperText('Empêche de suivre les liens de cette page'),

                        Forms\Components\TextInput::make('canonical_url')
                            ->label('URL canonique')
                            ->url()
                            ->placeholder('Laissez vide pour URL automatique'),

                        Forms\Components\TextInput::make('priority')
                            ->label('Priorité sitemap')
                            ->numeric()
                            ->default(0.5)
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.1),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('route_name')
                    ->label('Route')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('page_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'home' => 'Accueil',
                        'listing' => 'Liste',
                        'detail' => 'Détail',
                        'static' => 'Statique',
                        'search' => 'Recherche',
                        'category' => 'Catégorie',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Long.')
                    ->formatStateUsing(fn (?string $state): string => strlen($state ?? '').'/70'),

                Tables\Columns\IconColumn::make('is_noindex')
                    ->label('NoIndex')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('page_type')
                    ->label('Type de page')
                    ->options([
                        'home' => 'Accueil',
                        'listing' => 'Liste',
                        'detail' => 'Détail',
                        'static' => 'Statique',
                        'search' => 'Recherche',
                    ]),

                Tables\Filters\TernaryFilter::make('is_noindex')
                    ->label('NoIndex'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('route_name');
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
            'index' => Pages\ListSeoData::route('/'),
            'create' => Pages\CreateSeoData::route('/create'),
            'edit' => Pages\EditSeoData::route('/{record}/edit'),
        ];
    }
}
