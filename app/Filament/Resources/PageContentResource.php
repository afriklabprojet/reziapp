<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageContentResource\Pages;
use App\Models\PageContent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageContentResource extends Resource
{
    protected static ?string $model = PageContent::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Contenus des pages';

    protected static ?string $modelLabel = 'Contenu de page';

    protected static ?string $pluralModelLabel = 'Contenus des pages';

    protected static ?string $navigationGroup = 'Contenu';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('page_title')
                            ->label('Titre de la page')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('page_slug')
                            ->label('Identifiant (slug)')
                            ->required()
                            ->maxLength(255)
                            ->disabled()
                            ->helperText('Ne peut pas être modifié'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Page active')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('Titre SEO')
                            ->maxLength(70)
                            ->helperText('60-70 caractères recommandés'),
                        Forms\Components\Textarea::make('meta_description')
                            ->label('Description SEO')
                            ->maxLength(160)
                            ->rows(2)
                            ->helperText('150-160 caractères recommandés'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Options Boost')
                    ->description('Plans tarifaires affichés sur la page /tarifs')
                    ->visible(fn ($record) => $record?->page_slug === 'tarifs')
                    ->schema([
                        Forms\Components\Repeater::make('data.boost_plans')
                            ->label('Plans Boost')
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('emoji')
                                            ->label('Emoji')
                                            ->maxLength(5)
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nom')
                                            ->required()
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('price')
                                            ->label('Prix (FCFA)')
                                            ->numeric()
                                            ->required()
                                            ->suffix('FCFA')
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('duration_label')
                                            ->label('Durée')
                                            ->placeholder('30 jours de boost')
                                            ->columnSpan(1),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('badge')
                                            ->label('Badge (ex: POPULAIRE)')
                                            ->columnSpan(1),
                                        Forms\Components\Toggle::make('popular')
                                            ->label('Plan mis en avant')
                                            ->columnSpan(1),
                                    ]),
                                Forms\Components\TagsInput::make('features')
                                    ->label('Fonctionnalités (une par entrée)')
                                    ->placeholder('Ajouter une fonctionnalité'),
                            ])
                            ->itemLabel(fn (array $state): ?string => ($state['emoji'] ?? '').' '.($state['name'] ?? 'Plan'))
                            ->collapsible()
                            ->reorderableWithButtons()
                            ->maxItems(5),
                    ]),

                Forms\Components\Section::make('FAQ')
                    ->description('Questions/Réponses affichées sur la page /tarifs')
                    ->visible(fn ($record) => $record?->page_slug === 'tarifs')
                    ->schema([
                        Forms\Components\Repeater::make('data.faq')
                            ->label('Questions fréquentes')
                            ->schema([
                                Forms\Components\TextInput::make('q')
                                    ->label('Question')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('a')
                                    ->label('Réponse')
                                    ->required()
                                    ->rows(3),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['q'] ?? 'Question')
                            ->collapsible()
                            ->reorderableWithButtons()
                            ->maxItems(10),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('page_title')
                    ->label('Page')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('page_slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('editor.name')
                    ->label('Modifié par')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Dernière modification')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => match($record->page_slug) {
                        'about' => route('pages.about'),
                        'contact' => route('pages.contact'),
                        'cgu' => route('pages.cgu'),
                        'confidentialite' => route('pages.confidentialite'),
                        'mentions-legales' => route('pages.mentions-legales'),
                        'faq' => route('pages.faq'),
                        'guide-proprietaire' => route('pages.guide-proprietaire'),
                        'tarifs' => route('pages.tarifs'),
                        'home' => route('home'),
                        default => route('home'),
                    }, shouldOpenInNewTab: true),
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Aucun contenu')
            ->emptyStateDescription('Les contenus des pages frontend.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPageContents::route('/'),
            'edit' => Pages\EditPageContent::route('/{record}/edit'),
        ];
    }
}
