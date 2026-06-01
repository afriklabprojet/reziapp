<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutoReplyResource\Pages;
use App\Models\AutoReply;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AutoReplyResource extends Resource
{
    protected static ?string $model = AutoReply::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Messagerie';

    protected static ?string $navigationLabel = 'Réponses automatiques';

    protected static ?string $modelLabel = 'Réponse automatique';

    protected static ?string $pluralModelLabel = 'Réponses automatiques';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'residence']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Réponse automatique')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence (optionnel)')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('trigger_type')
                            ->label('Type de déclencheur')
                            ->maxLength(100),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                        Forms\Components\TextInput::make('delay_minutes')
                            ->label('Délai (minutes)')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('Message')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(5)
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
                Tables\Columns\TextColumn::make('residence.title')
                    ->label('Résidence')
                    ->limit(20),
                Tables\Columns\TextColumn::make('trigger_type')
                    ->label('Déclencheur'),
                Tables\Columns\TextColumn::make('delay_minutes')
                    ->label('Délai (min)'),
                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Utilisations')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Dernière utilisation')
                    ->date('d/m/Y'),
            ])
            ->filters([
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
            'index'  => Pages\ListAutoReplies::route('/'),
            'create' => Pages\CreateAutoReply::route('/create'),
            'edit'   => Pages\EditAutoReply::route('/{record}/edit'),
        ];
    }
}
