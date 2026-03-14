<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\CommuneList;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Coupons';

    protected static ?string $modelLabel = 'Code promo';

    protected static ?string $pluralModelLabel = 'Codes promo';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Coupon')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informations')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->default(fn () => Str::upper(Str::random(8)))
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('generate')
                                            ->icon('heroicon-o-arrow-path')
                                            ->action(fn ($set) => $set('code', Str::upper(Str::random(8)))),
                                    ),
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom interne')
                                    ->maxLength(100)
                                    ->helperText('Pour identification interne'),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('scope')
                                    ->label('Portée')
                                    ->options([
                                        'global' => 'Global (toute la plateforme)',
                                        'residence' => 'Résidence spécifique',
                                        'owner' => 'Propriétaire spécifique',
                                        'user' => 'Utilisateur spécifique',
                                    ])
                                    ->default('global')
                                    ->live(),
                                Forms\Components\Select::make('residence_id')
                                    ->label('Résidence')
                                    ->relationship('residence', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn ($get) => $get('scope') === 'residence'),
                                Forms\Components\Select::make('user_id')
                                    ->label('Propriétaire/Utilisateur')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn ($get) => in_array($get('scope'), ['owner', 'user'])),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Réduction')
                            ->icon('heroicon-o-receipt-percent')
                            ->schema([
                                Forms\Components\Select::make('discount_type')
                                    ->label('Type de réduction')
                                    ->options([
                                        'percentage' => 'Pourcentage (%)',
                                        'fixed' => 'Montant fixe (FCFA)',
                                        'free_nights' => 'Nuits gratuites',
                                    ])
                                    ->required()
                                    ->default('percentage')
                                    ->live(),
                                Forms\Components\TextInput::make('discount_value')
                                    ->label('Valeur de la réduction')
                                    ->numeric()
                                    ->required()
                                    ->suffix(fn ($get) => match($get('discount_type')) {
                                        'percentage' => '%',
                                        'fixed' => 'FCFA',
                                        'free_nights' => 'nuit(s)',
                                        default => '',
                                    })
                                    ->helperText(fn ($get) => match($get('discount_type')) {
                                        'percentage' => 'Ex: 10 pour 10% de réduction',
                                        'fixed' => 'Ex: 5000 pour 5000 FCFA de réduction',
                                        'free_nights' => 'Ex: 1 pour 1 nuit offerte',
                                        default => '',
                                    }),
                                Forms\Components\TextInput::make('max_discount')
                                    ->label('Réduction maximum (FCFA)')
                                    ->numeric()
                                    ->helperText('Plafonner la réduction (pour les %). Laisser vide pour aucune limite.')
                                    ->visible(fn ($get) => $get('discount_type') === 'percentage'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Conditions')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Forms\Components\TextInput::make('min_amount')
                                    ->label('Montant minimum (FCFA)')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Montant minimum de réservation requis'),
                                Forms\Components\TextInput::make('min_nights')
                                    ->label('Nuits minimum')
                                    ->numeric()
                                    ->default(1)
                                    ->helperText('Nombre minimum de nuits de séjour'),
                                Forms\Components\Toggle::make('first_booking_only')
                                    ->label('Première réservation uniquement')
                                    ->helperText('Réservé aux nouveaux utilisateurs')
                                    ->default(false),
                                Forms\Components\Select::make('allowed_communes')
                                    ->label('Zones autorisées')
                                    ->multiple()
                                    ->searchable()
                                    ->options(fn () => CommuneList::active()
                                        ->orderBy('name')
                                        ->pluck('name', 'name')
                                        ->toArray())
                                    ->helperText('Laisser vide pour toutes les zones'),
                                Forms\Components\Select::make('allowed_types')
                                    ->label('Types de résidences autorisés')
                                    ->multiple()
                                    ->options([
                                        'apartment' => 'Appartement',
                                        'studio' => 'Studio',
                                        'villa' => 'Villa',
                                        'house' => 'Maison',
                                        'room' => 'Chambre',
                                    ])
                                    ->helperText('Laisser vide pour tous les types'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Limites')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\TextInput::make('max_uses')
                                    ->label('Utilisations maximum')
                                    ->numeric()
                                    ->helperText('Nombre total d\'utilisations. Laisser vide pour illimité.'),
                                Forms\Components\TextInput::make('max_uses_per_user')
                                    ->label('Utilisations par utilisateur')
                                    ->numeric()
                                    ->default(1)
                                    ->helperText('Combien de fois un même utilisateur peut utiliser ce code'),
                                Forms\Components\TextInput::make('uses_count')
                                    ->label('Utilisations actuelles')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Validité')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Début de validité')
                                    ->helperText('Laisser vide pour immédiat'),
                                Forms\Components\DateTimePicker::make('expires_at')
                                    ->label('Fin de validité')
                                    ->helperText('Laisser vide pour jamais'),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Code actif')
                                    ->default(true)
                                    ->helperText('Désactiver pour suspendre le code'),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Code copié!')
                    ->weight('bold')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->limit(30)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('discount_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'percentage' => 'success',
                        'fixed' => 'info',
                        'free_nights' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'percentage' => 'Pourcentage',
                        'fixed' => 'Fixe',
                        'free_nights' => 'Nuits offertes',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('discount_value')
                    ->label('Valeur')
                    ->formatStateUsing(fn ($record) => match($record->discount_type) {
                        'percentage' => $record->discount_value.'%',
                        'fixed' => number_format($record->discount_value).' FCFA',
                        'free_nights' => $record->discount_value.' nuit(s)',
                        default => $record->discount_value,
                    }),
                Tables\Columns\TextColumn::make('scope')
                    ->label('Portée')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'global' => 'Global',
                        'residence' => 'Résidence',
                        'owner' => 'Propriétaire',
                        'user' => 'Utilisateur',
                        default => 'Global',
                    }),
                Tables\Columns\TextColumn::make('uses_count')
                    ->label('Utilisations')
                    ->formatStateUsing(
                        fn ($record) =>
                        $record->uses_count.($record->max_uses ? ' / '.$record->max_uses : ''),
                    )
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expire le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('Jamais')
                    ->color(
                        fn ($record) =>
                        $record->expires_at && $record->expires_at->isPast() ? 'danger' : null,
                    ),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('discount_type')
                    ->label('Type')
                    ->options([
                        'percentage' => 'Pourcentage',
                        'fixed' => 'Montant fixe',
                        'free_nights' => 'Nuits offertes',
                    ]),
                Tables\Filters\SelectFilter::make('scope')
                    ->label('Portée')
                    ->options([
                        'global' => 'Global',
                        'residence' => 'Résidence',
                        'owner' => 'Propriétaire',
                        'user' => 'Utilisateur',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
                Tables\Filters\Filter::make('expired')
                    ->label('Expirés')
                    ->query(fn ($query) => $query->where('expires_at', '<', now())),
                Tables\Filters\Filter::make('valid')
                    ->label('Valides')
                    ->query(fn ($query) => $query->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', now());
                    })),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle')
                    ->label(fn ($record) => $record->is_active ? 'Désactiver' : 'Activer')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn ($record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active])),
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activer')
                        ->icon('heroicon-o-play')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Désactiver')
                        ->icon('heroicon-o-pause')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
