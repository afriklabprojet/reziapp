<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageTemplateResource\Pages;
use App\Models\MessageTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MessageTemplateResource extends Resource
{
    protected static ?string $model = MessageTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Messagerie';

    protected static ?string $navigationLabel = 'Modèles de messages';

    protected static ?string $modelLabel = 'Modèle de message';

    protected static ?string $pluralModelLabel = 'Modèles de messages';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Modèle')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'greeting'     => 'Bienvenue',
                                'availability' => 'Disponibilité',
                                'pricing'      => 'Tarifs',
                                'rules'        => 'Règles',
                                'directions'   => 'Directions',
                                'thank_you'    => 'Remerciements',
                                'custom'       => 'Personnalisé',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('shortcut')
                            ->label('Raccourci')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('language')
                            ->label('Langue')
                            ->default('fr')
                            ->maxLength(10),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                        Forms\Components\Toggle::make('is_system')
                            ->label('Modèle système'),
                    ])->columns(2),

                Forms\Components\Section::make('Contenu')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('Contenu')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur'),
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'greeting'     => 'Bienvenue',
                        'availability' => 'Disponibilité',
                        'pricing'      => 'Tarifs',
                        'rules'        => 'Règles',
                        'directions'   => 'Directions',
                        'thank_you'    => 'Remerciements',
                        default        => 'Personnalisé',
                    }),
                Tables\Columns\TextColumn::make('shortcut')
                    ->label('Raccourci'),
                Tables\Columns\TextColumn::make('language')
                    ->label('Langue'),
                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Utilisations')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_system')
                    ->label('Système')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'greeting'     => 'Bienvenue',
                        'availability' => 'Disponibilité',
                        'pricing'      => 'Tarifs',
                        'rules'        => 'Règles',
                        'directions'   => 'Directions',
                        'thank_you'    => 'Remerciements',
                        'custom'       => 'Personnalisé',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMessageTemplates::route('/'),
            'create' => Pages\CreateMessageTemplate::route('/create'),
            'edit'   => Pages\EditMessageTemplate::route('/{record}/edit'),
        ];
    }
}
