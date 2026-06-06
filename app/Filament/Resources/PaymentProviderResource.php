<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentProviderResource\Pages;
use App\Models\PaymentProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentProviderResource extends Resource
{
    protected static ?string $model = PaymentProvider::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Paiements';

    protected static ?string $navigationLabel = 'Moyens de paiement';

    protected static ?string $modelLabel = 'Moyen de paiement';

    protected static ?string $pluralModelLabel = 'Moyens de paiement';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

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
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->helperText('Identifiant unique (ex: orange_money, mtn_momo)'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Logo')
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo du moyen de paiement')
                            ->image()
                            ->disk('public')
                            ->directory('payment-providers')
                            ->visibility('public')
                            ->imageResizeMode('contain')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                            ->maxSize(2048)
                            ->helperText('Format recommandé : PNG ou SVG, 200×200px, max 2 Mo')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Configuration financière')
                    ->schema([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Montant minimum (FCFA)')
                            ->numeric()
                            ->default(100)
                            ->required(),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Montant maximum (FCFA)')
                            ->numeric()
                            ->default(5000000)
                            ->required(),
                        Forms\Components\TextInput::make('fee_percentage')
                            ->label('Frais (%)')
                            ->numeric()
                            ->step(0.01)
                            ->default(0),
                        Forms\Components\TextInput::make('fee_fixed')
                            ->label('Frais fixes (FCFA)')
                            ->numeric()
                            ->step(0.01)
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                        Forms\Components\Toggle::make('is_sandbox')
                            ->label('Mode sandbox (test)')
                            ->default(false),
                        Forms\Components\TextInput::make('display_order')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->size(40)
                    ->defaultImageUrl(asset('images/payment-placeholder.png')),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('fee_percentage')
                    ->label('Frais')
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_sandbox')
                    ->label('Sandbox')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('display_order')
                    ->label('Ordre')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
                Tables\Filters\TernaryFilter::make('is_sandbox')
                    ->label('Sandbox'),
            ])
            ->reorderable('display_order')
            ->defaultSort('display_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPaymentProviders::route('/'),
            'create' => Pages\CreatePaymentProvider::route('/create'),
            'edit'   => Pages\EditPaymentProvider::route('/{record}/edit'),
        ];
    }
}
