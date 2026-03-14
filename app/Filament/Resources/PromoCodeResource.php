<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Codes promo';

    protected static ?string $modelLabel = 'Code promo';

    protected static ?string $pluralModelLabel = 'Codes promo';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du code')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Réduction')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'percentage' => 'Pourcentage',
                                'fixed' => 'Montant fixe',
                            ])
                            ->default('percentage')
                            ->required(),
                        Forms\Components\TextInput::make('value')
                            ->label('Valeur')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Montant minimum')
                            ->numeric()
                            ->prefix('FCFA'),
                        Forms\Components\TextInput::make('max_discount')
                            ->label('Réduction max')
                            ->numeric()
                            ->prefix('FCFA'),
                    ])->columns(2),

                Forms\Components\Section::make('Validité')
                    ->schema([
                        Forms\Components\DatePicker::make('starts_at')
                            ->label('Début'),
                        Forms\Components\DatePicker::make('expires_at')
                            ->label('Expiration'),
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Utilisations max')
                            ->numeric(),
                        Forms\Components\TextInput::make('uses_count')
                            ->label('Utilisations')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'percentage' => 'Pourcentage',
                        'fixed' => 'Montant fixe',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valeur')
                    ->formatStateUsing(
                        fn ($record): string =>
                        $record->type === 'percentage' ? $record->value.'%' : number_format($record->value, 0, ',', ' ').' FCFA',
                    ),
                Tables\Columns\TextColumn::make('uses_count')
                    ->label('Utilisations')
                    ->formatStateUsing(
                        fn ($record): string =>
                        $record->uses_count.($record->max_uses ? '/'.$record->max_uses : ''),
                    ),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->placeholder('Illimité'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'percentage' => 'Pourcentage',
                        'fixed' => 'Montant fixe',
                    ]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
