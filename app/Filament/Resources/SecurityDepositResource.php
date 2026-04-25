<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityDepositResource\Pages;
use App\Models\SecurityDeposit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SecurityDepositResource extends Resource
{
    protected static ?string $model = SecurityDeposit::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Contrats & Cautions';

    protected static ?string $navigationLabel = 'Cautions';

    protected static ?string $modelLabel = 'Caution';

    protected static ?string $pluralModelLabel = 'Cautions';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['owner', 'tenant', 'residence']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Parties')
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
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('Montants')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant de la caution (FCFA)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('returned_amount')
                            ->label('Montant remboursé (FCFA)')
                            ->numeric(),
                        Forms\Components\Select::make('currency')
                            ->label('Devise')
                            ->options(['XOF' => 'FCFA (XOF)', 'EUR' => 'Euro (EUR)'])
                            ->default('XOF'),
                    ])->columns(3),

                Forms\Components\Section::make('Statut & Paiement')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                SecurityDeposit::STATUS_PENDING        => 'En attente',
                                SecurityDeposit::STATUS_HELD           => 'Retenu',
                                SecurityDeposit::STATUS_PARTIAL_RETURN => 'Remboursement partiel',
                                SecurityDeposit::STATUS_RETURNED       => 'Remboursé',
                                SecurityDeposit::STATUS_FORFEITED      => 'Confisqué',
                                SecurityDeposit::STATUS_DISPUTED       => 'Litigieux',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('payment_method')
                            ->label('Mode de paiement'),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Payé le'),
                        Forms\Components\DateTimePicker::make('returned_at')
                            ->label('Remboursé le'),
                        Forms\Components\DatePicker::make('return_deadline')
                            ->label('Date limite de remboursement'),
                    ])->columns(2),

                Forms\Components\Section::make('Déductions')
                    ->schema([
                        Forms\Components\Textarea::make('deduction_reasons')
                            ->label('Motifs de déduction')
                            ->rows(3),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->badge()
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->badge()
                    ->label('Propriétaire')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->badge()
                    ->label('Locataire')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->badge()
                    ->label('Montant')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ').' FCFA')
                    ->sortable(),
                Tables\Columns\TextColumn::make('returned_amount')
                    ->badge()
                    ->label('Remboursé')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ').' FCFA' : '-'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'warning' => [SecurityDeposit::STATUS_PENDING],
                        'info'    => [SecurityDeposit::STATUS_HELD],
                        'success' => [SecurityDeposit::STATUS_RETURNED],
                        'primary' => [SecurityDeposit::STATUS_PARTIAL_RETURN],
                        'danger'  => [SecurityDeposit::STATUS_FORFEITED, SecurityDeposit::STATUS_DISPUTED],
                    ])
                    ->formatStateUsing(fn ($s) => match ($s) {
                        SecurityDeposit::STATUS_PENDING        => 'En attente',
                        SecurityDeposit::STATUS_HELD           => 'Retenu',
                        SecurityDeposit::STATUS_PARTIAL_RETURN => 'Partiel',
                        SecurityDeposit::STATUS_RETURNED       => 'Remboursé',
                        SecurityDeposit::STATUS_FORFEITED      => 'Confisqué',
                        SecurityDeposit::STATUS_DISPUTED       => 'Litigieux',
                        default                                => $s,
                    }),
                Tables\Columns\TextColumn::make('paid_at')
                    ->badge()
                    ->label('Payé le')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('return_deadline')
                    ->badge()
                    ->label('Limite remboursement')
                    ->date('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        SecurityDeposit::STATUS_PENDING        => 'En attente',
                        SecurityDeposit::STATUS_HELD           => 'Retenu',
                        SecurityDeposit::STATUS_PARTIAL_RETURN => 'Remboursement partiel',
                        SecurityDeposit::STATUS_RETURNED       => 'Remboursé',
                        SecurityDeposit::STATUS_FORFEITED      => 'Confisqué',
                        SecurityDeposit::STATUS_DISPUTED       => 'Litigieux',
                    ]),
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
            'index'  => Pages\ListSecurityDeposits::route('/'),
            'create' => Pages\CreateSecurityDeposit::route('/create'),
            'edit'   => Pages\EditSecurityDeposit::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', SecurityDeposit::STATUS_DISPUTED)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
