<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromotionResource\Pages;
use App\Models\Promotion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Promotions';

    protected static ?string $modelLabel = 'Promotion';

    protected static ?string $pluralModelLabel = 'Promotions';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Propriétaire')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Réduction')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->label('Type de réduction')
                            ->options([
                                'percentage' => 'Pourcentage',
                                'fixed' => 'Montant fixe',
                                'free_nights' => 'Nuits gratuites',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('discount_value')
                            ->label('Valeur')
                            ->numeric()
                            ->required()
                            ->suffix(fn ($get) => $get('discount_type') === 'percentage' ? '%' : 'FCFA'),
                        Forms\Components\TextInput::make('free_nights_min')
                            ->label('Nuits minimum pour nuits gratuites')
                            ->numeric()
                            ->visible(fn ($get) => $get('discount_type') === 'free_nights'),
                        Forms\Components\TextInput::make('min_nights')
                            ->label('Nuits minimum requises')
                            ->numeric()
                            ->default(1),
                    ])->columns(2),

                Forms\Components\Section::make('Validité')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Date de début')
                            ->required(),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Date de fin')
                            ->required(),
                        Forms\Components\DatePicker::make('booking_start')
                            ->label('Séjours à partir du'),
                        Forms\Components\DatePicker::make('booking_end')
                            ->label('Séjours jusqu\'au'),
                    ])->columns(2),

                Forms\Components\Section::make('Limites et statut')
                    ->schema([
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Utilisations max')
                            ->numeric()
                            ->helperText('Laissez vide pour illimité'),
                        Forms\Components\Placeholder::make('uses_count_display')
                            ->label('Utilisations')
                            ->content(fn ($record) => $record?->uses_count ?? 0)
                            ->visibleOn('edit'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Mise en avant')
                            ->default(false),
                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('residence.name')
                    ->label('Résidence')
                    ->limit(20)
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'percentage' => 'Pourcentage',
                        'fixed' => 'Fixe',
                        'free_nights' => 'Nuits gratuites',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('discount_value')
                    ->label('Valeur')
                    ->formatStateUsing(fn ($record) => $record->discount_type === 'percentage'
                        ? $record->discount_value.'%'
                        : number_format($record->discount_value, 0, ',', ' ').' FCFA'),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('uses_count')
                    ->label('Utilisations')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Vedette')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('discount_type')
                    ->label('Type')
                    ->options([
                        'percentage' => 'Pourcentage',
                        'fixed' => 'Montant fixe',
                        'free_nights' => 'Nuits gratuites',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Mise en avant'),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'edit' => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count() ?: null;
    }
}
