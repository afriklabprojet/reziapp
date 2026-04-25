<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuidebookResource\Pages;
use App\Models\Guidebook;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GuidebookResource extends Resource
{
    protected static ?string $model = Guidebook::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Réservations';

    protected static ?string $modelLabel = 'Guide';

    protected static ?string $pluralModelLabel = 'Guides de bienvenue';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations principales')
                    ->schema([
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Propriétaire')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('welcome_message')
                            ->label('Message de bienvenue')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Accès & WiFi')
                    ->schema([
                        Forms\Components\TextInput::make('wifi_name')
                            ->label('Nom WiFi (SSID)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('wifi_password')
                            ->label('Mot de passe WiFi')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                    ])->columns(2),
                Forms\Components\Section::make('Instructions')
                    ->schema([
                        Forms\Components\Textarea::make('house_rules_details')
                            ->label('Règles de la maison')
                            ->rows(3),
                        Forms\Components\Textarea::make('parking_info')
                            ->label('Parking')
                            ->rows(2),
                        Forms\Components\Textarea::make('transport_info')
                            ->label('Transports')
                            ->rows(2),
                        Forms\Components\Textarea::make('checkout_instructions')
                            ->label('Instructions de départ')
                            ->rows(2),
                        Forms\Components\Textarea::make('emergency_info')
                            ->label('Urgences')
                            ->rows(2),
                    ])->columns(2),
                Forms\Components\Section::make('Publication')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('Publié')
                            ->default(false),
                        Forms\Components\TextInput::make('access_token')
                            ->label('Token d\'accès')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Généré automatiquement'),
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Propriétaire')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Publié'),
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
            'index'  => Pages\ListGuidebooks::route('/'),
            'create' => Pages\CreateGuidebook::route('/create'),
            'edit'   => Pages\EditGuidebook::route('/{record}/edit'),
        ];
    }
}
