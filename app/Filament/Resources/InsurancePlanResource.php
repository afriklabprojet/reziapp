<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InsurancePlanResource\Pages;
use App\Models\InsurancePlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InsurancePlanResource extends Resource
{
    protected static ?string $model = InsurancePlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Assurances';

    protected static ?string $navigationLabel = 'Plans d\'assurance';

    protected static ?string $modelLabel = 'Plan d\'assurance';

    protected static ?string $pluralModelLabel = 'Plans d\'assurance';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du plan')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2),
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
                        Forms\Components\TextInput::make('rate')
                            ->label('Taux (%)')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Prime minimale (FCFA)')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_coverage')
                            ->label('Couverture maximale (FCFA)')
                            ->numeric(),
                        Forms\Components\TextInput::make('deductible')
                            ->label('Franchise (FCFA)')
                            ->numeric(),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('rate')
                    ->label('Taux')
                    ->formatStateUsing(fn ($state) => $state.'%'),
                Tables\Columns\TextColumn::make('min_amount')
                    ->label('Prime min')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ').' FCFA' : '-'),
                Tables\Columns\TextColumn::make('max_coverage')
                    ->label('Couverture max')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ').' FCFA' : '-'),
                Tables\Columns\TextColumn::make('deductible')
                    ->label('Franchise')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ').' FCFA' : '-'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable(),
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
            'index'  => Pages\ListInsurancePlans::route('/'),
            'create' => Pages\CreateInsurancePlan::route('/create'),
            'edit'   => Pages\EditInsurancePlan::route('/{record}/edit'),
        ];
    }
}
