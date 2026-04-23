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
                            ->placeholder('home, residences.index, pages.about…'),

                        Forms\Components\TextInput::make('url_pattern')
                            ->label("Pattern d'URL")
                            ->placeholder('/residences/{slug}'),

                        Forms\Components\Select::make('page_type')
                            ->label('Type de page')
                            ->options([
                                'home'     => "Page d'accueil",
                                'listing'  => 'Liste',
                                'detail'   => 'Détail',
                                'static'   => 'Page statique',
                                'search'   => 'Recherche',
                                'category' => 'Catégorie',
                            ])
                            ->required(),

                        Forms\Components\Select::make('locale')
                            ->label('Langue')
                            ->options(['fr' => 'Français', 'en' => 'Anglais'])
                            ->default('fr')
                            ->required(),
                    ])->columns(4),

                Forms\Components\Section::make('Méta-données')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('Titre SEO')
                            ->required()
                            ->maxLength(70)
                            ->helperText('Max 70 caractères')
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                                $set('_title_len', strlen($state ?? ''))),

                        Forms\Components\Placeholder::make('_title_len')
                            ->label('Longueur')
                            ->content(fn (Forms\Get $get): string =>
                                strlen($get('meta_title') ?? '').'/70'),

                        Forms\Components\Textarea::make('meta_description')
                            ->label('Méta description')
                            ->required()
                            ->rows(3)
                            ->maxLength(160)
                            ->helperText('Max 160 caractères')
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('keywords')
                            ->label('Mots-clés')
                            ->separator(',')
                            ->placeholder('Ajouter un mot-clé')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Open Graph (Réseaux sociaux)')
                    ->schema([
                        Forms\Components\TextInput::make('og_title')
                            ->label('Titre OG')
                            ->maxLength(95)
                            ->placeholder('Même que le titre SEO si vide'),

                        Forms\Components\Select::make('og_type')
                            ->label('Type OG')
                            ->options([
                                'website' => 'Website',
                                'article' => 'Article',
                                'place'   => 'Place',
                            ])
                            ->default('website'),

                        Forms\Components\Textarea::make('og_description')
                            ->label('Description OG')
                            ->rows(2)
                            ->maxLength(200)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('og_image')
                            ->label('URL image OG')
                            ->url()
                            ->placeholder('https://… (1200×630px recommandé)')
                            ->columnSpanFull(),
                    ])->columns(2)->collapsed(),

                Forms\Components\Section::make('Données structurées (JSON-LD)')
                    ->schema([
                        Forms\Components\Textarea::make('schema_json')
                            ->label('JSON-LD')
                            ->rows(8)
                            ->helperText('Laissez vide pour générer automatiquement via SeoService')
                            ->columnSpanFull(),
                    ])->collapsed(),

                Forms\Components\Section::make('Options avancées')
                    ->schema([
                        Forms\Components\TextInput::make('canonical_url')
                            ->label('URL canonique')
                            ->url()
                            ->placeholder('Laissez vide = URL automatique')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('priority')
                            ->label('Priorité sitemap')
                            ->numeric()
                            ->default(0.5)
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.1),

                        Forms\Components\Toggle::make('is_noindex')
                            ->label('NoIndex')
                            ->helperText('Ne pas indexer cette page'),

                        Forms\Components\Toggle::make('is_nofollow')
                            ->label('NoFollow')
                            ->helperText('Ne pas suivre les liens'),
                    ])->columns(5)->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('route_name')
                    ->label('Route')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('page_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'home'    => 'success',
                        'listing' => 'info',
                        'detail'  => 'primary',
                        'static'  => 'gray',
                        'search'  => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'home'     => 'Accueil',
                        'listing'  => 'Liste',
                        'detail'   => 'Détail',
                        'static'   => 'Statique',
                        'search'   => 'Recherche',
                        'category' => 'Catégorie',
                        default    => $state ?? '—',
                    }),

                Tables\Columns\TextColumn::make('meta_title')
                    ->label('Titre SEO')
                    ->limit(45)
                    ->searchable(),

                Tables\Columns\TextColumn::make('meta_title')
                    ->label('Long.')
                    ->formatStateUsing(fn (?string $state): string =>
                        strlen($state ?? '').'/70 '.((strlen($state ?? '') > 70) ? '⚠️' : '✓')),

                Tables\Columns\IconColumn::make('is_noindex')
                    ->label('NoIndex')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye'),

                Tables\Columns\TextColumn::make('locale')
                    ->label('Lang')
                    ->badge(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('page_type')
                    ->label('Type de page')
                    ->options([
                        'home'    => 'Accueil',
                        'listing' => 'Liste',
                        'detail'  => 'Détail',
                        'static'  => 'Statique',
                        'search'  => 'Recherche',
                    ]),

                Tables\Filters\TernaryFilter::make('is_noindex')
                    ->label('NoIndex'),

                Tables\Filters\SelectFilter::make('locale')
                    ->label('Langue')
                    ->options(['fr' => 'Français', 'en' => 'Anglais']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSeoData::route('/'),
            'create' => Pages\CreateSeoData::route('/create'),
            'edit'   => Pages\EditSeoData::route('/{record}/edit'),
        ];
    }
}
