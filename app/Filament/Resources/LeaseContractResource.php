<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaseContractResource\Pages;
use App\Models\LeaseContract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaseContractResource extends Resource
{
    protected static ?string $model = LeaseContract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Contrats & Cautions';

    protected static ?string $navigationLabel = 'Contrats de bail';

    protected static ?string $modelLabel = 'Contrat de bail';

    protected static ?string $pluralModelLabel = 'Contrats de bail';

    protected static ?int $navigationSort = 1;

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
                            ->preload()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Conditions')
                    ->schema([
                        Forms\Components\Select::make('lease_type')
                            ->label('Type de bail')
                            ->options([
                                LeaseContract::TYPE_SHORT_TERM => 'Court terme',
                                LeaseContract::TYPE_MONTHLY    => 'Mensuel',
                                LeaseContract::TYPE_FIXED_TERM => 'Durée déterminée',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                LeaseContract::STATUS_DRAFT          => 'Brouillon',
                                LeaseContract::STATUS_PENDING_TENANT => 'En attente locataire',
                                LeaseContract::STATUS_PENDING_OWNER  => 'En attente propriétaire',
                                LeaseContract::STATUS_ACTIVE         => 'Actif',
                                LeaseContract::STATUS_TERMINATED     => 'Résilié',
                                LeaseContract::STATUS_EXPIRED        => 'Expiré',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Date de fin'),
                        Forms\Components\TextInput::make('monthly_rent')
                            ->label('Loyer mensuel (FCFA)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('deposit_amount')
                            ->label('Caution (FCFA)')
                            ->numeric(),
                        Forms\Components\TextInput::make('charges_amount')
                            ->label('Charges (FCFA)')
                            ->numeric(),
                        Forms\Components\TextInput::make('payment_day')
                            ->label('Jour de paiement')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31),
                    ])->columns(2),

                Forms\Components\Section::make('Signatures')
                    ->schema([
                        Forms\Components\DateTimePicker::make('owner_signed_at')
                            ->label('Signé par le propriétaire'),
                        Forms\Components\DateTimePicker::make('tenant_signed_at')
                            ->label('Signé par le locataire'),
                    ])->columns(2),

                Forms\Components\Section::make('Clauses')
                    ->schema([
                        Forms\Components\Textarea::make('special_clauses')
                            ->label('Clauses spéciales')
                            ->rows(4),
                    ]),
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->badge()
                    ->label('Locataire')
                    ->searchable(),
                Tables\Columns\TextColumn::make('residence.title')
                    ->badge()
                    ->label('Résidence')
                    ->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'gray'    => LeaseContract::STATUS_DRAFT,
                        'warning' => [LeaseContract::STATUS_PENDING_TENANT, LeaseContract::STATUS_PENDING_OWNER],
                        'success' => LeaseContract::STATUS_ACTIVE,
                        'danger'  => [LeaseContract::STATUS_TERMINATED, LeaseContract::STATUS_EXPIRED],
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        LeaseContract::STATUS_DRAFT          => 'Brouillon',
                        LeaseContract::STATUS_PENDING_TENANT => 'Att. locataire',
                        LeaseContract::STATUS_PENDING_OWNER  => 'Att. propriétaire',
                        LeaseContract::STATUS_ACTIVE         => 'Actif',
                        LeaseContract::STATUS_TERMINATED     => 'Résilié',
                        LeaseContract::STATUS_EXPIRED        => 'Expiré',
                        default                              => $state,
                    }),
                Tables\Columns\TextColumn::make('monthly_rent')
                    ->badge()
                    ->label('Loyer')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ').' FCFA')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->badge()
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->badge()
                    ->label('Fin')
                    ->date('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        LeaseContract::STATUS_DRAFT          => 'Brouillon',
                        LeaseContract::STATUS_PENDING_TENANT => 'En attente locataire',
                        LeaseContract::STATUS_PENDING_OWNER  => 'En attente propriétaire',
                        LeaseContract::STATUS_ACTIVE         => 'Actif',
                        LeaseContract::STATUS_TERMINATED     => 'Résilié',
                        LeaseContract::STATUS_EXPIRED        => 'Expiré',
                    ]),
                Tables\Filters\SelectFilter::make('lease_type')
                    ->label('Type')
                    ->options([
                        LeaseContract::TYPE_SHORT_TERM => 'Court terme',
                        LeaseContract::TYPE_MONTHLY    => 'Mensuel',
                        LeaseContract::TYPE_FIXED_TERM => 'Durée déterminée',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelationsManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeaseContracts::route('/'),
            'create' => Pages\CreateLeaseContract::route('/create'),
            'edit'   => Pages\EditLeaseContract::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', LeaseContract::STATUS_PENDING_TENANT)
            ->orWhere('status', LeaseContract::STATUS_PENDING_OWNER)
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
