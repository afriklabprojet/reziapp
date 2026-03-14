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
