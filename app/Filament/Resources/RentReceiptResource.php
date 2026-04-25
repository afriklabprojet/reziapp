<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RentReceiptResource\Pages;
use App\Models\RentReceipt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RentReceiptResource extends Resource
{
    protected static ?string $model = RentReceipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Contrats & Cautions';

    protected static ?string $navigationLabel = 'Quittances de loyer';

    protected static ?string $modelLabel = 'Quittance';

    protected static ?string $pluralModelLabel = 'Quittances de loyer';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['owner', 'tenant', 'residence']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Quittance')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('owner_id')
                            ->label('Propriétaire')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('tenant_id')
                            ->label('Locataire')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('lease_contract_id')
                            ->label('Contrat de bail')
                            ->relationship('leaseContract', 'reference')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Payé')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('Période & Montants')
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Début période')
                            ->required(),
                        Forms\Components\DatePicker::make('period_end')
                            ->label('Fin période')
                            ->required(),
                        Forms\Components\TextInput::make('rent_amount')
                            ->label('Loyer (FCFA)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('charges_amount')
                            ->label('Charges (FCFA)')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total (FCFA)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('currency')
                            ->label('Devise')
                            ->default('XOF')
                            ->maxLength(10),
                    ])->columns(2),

                Forms\Components\Section::make('Paiement')
                    ->schema([
                        Forms\Components\TextInput::make('payment_method')
                            ->label('Méthode de paiement')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Référence paiement')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Date de paiement'),
                        Forms\Components\Toggle::make('sent_by_email')
                            ->label('Envoyé par email'),
                        Forms\Components\Toggle::make('sent_by_whatsapp')
                            ->label('Envoyé par WhatsApp'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Locataire'),
                Tables\Columns\TextColumn::make('residence.title')
                    ->label('Résidence')
                    ->limit(20),
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Période')
                    ->date('M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ').' FCFA'),
                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Payé')
                    ->boolean(),
                Tables\Columns\IconColumn::make('sent_by_email')
                    ->label('Email')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Payé'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRentReceipts::route('/'),
            'create' => Pages\CreateRentReceipt::route('/create'),
            'edit'   => Pages\EditRentReceipt::route('/{record}/edit'),
        ];
    }
}
