<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Abonnements';

    protected static ?string $navigationLabel = 'Plans d\'abonnement';

    protected static ?string $modelLabel = 'Plan d\'abonnement';

    protected static ?string $pluralModelLabel = 'Plans d\'abonnement';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('Tarification')
                    ->schema([
                        Forms\Components\TextInput::make('price_monthly')
                            ->label('Prix mensuel (FCFA)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('price_yearly')
                            ->label('Prix annuel (FCFA)')
                            ->numeric(),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Taux de commission (%)')
                            ->numeric()
                            ->step(0.01),
                    ])->columns(3),

                Forms\Components\Section::make('Limites')
                    ->schema([
                        Forms\Components\TextInput::make('max_residences')
                            ->label('Résidences max')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_photos_per_residence')
                            ->label('Photos max / résidence')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_sponsored_per_month')
                            ->label('Sponsorisés max / mois')
                            ->numeric(),
                    ])->columns(3),

                Forms\Components\Section::make('Fonctionnalités')
                    ->schema([
                        Forms\Components\Toggle::make('priority_support')
                            ->label('Support prioritaire'),
                        Forms\Components\Toggle::make('analytics_advanced')
                            ->label('Analytics avancés'),
                        Forms\Components\Toggle::make('auto_replies')
                            ->label('Réponses automatiques'),
                        Forms\Components\Toggle::make('calendar_sync')
                            ->label('Synchronisation calendrier'),
                        Forms\Components\Toggle::make('featured_badge')
                            ->label('Badge "En vedette"'),
                    ])->columns(3),
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
                Tables\Columns\TextColumn::make('price_monthly')
                    ->label('Mensuel')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ').' FCFA')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_yearly')
                    ->label('Annuel')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ').' FCFA' : '-'),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Commission')
                    ->formatStateUsing(fn ($state) => $state.'%'),
                Tables\Columns\TextColumn::make('max_residences')
                    ->label('Résidences max'),
                Tables\Columns\IconColumn::make('priority_support')
                    ->label('Support prioritaire')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit'   => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
