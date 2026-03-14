<?php

namespace App\Filament\Resources;

use App\Filament\Exports\UserExporter;
use App\Filament\Resources\OwnerResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class OwnerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Gestion des hôtes';

    protected static ?string $navigationLabel = 'Propriétaires';

    protected static ?string $modelLabel = 'Propriétaire';

    protected static ?string $pluralModelLabel = 'Propriétaires';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'proprietaires';

    // Filtrer uniquement les propriétaires
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'owner');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du propriétaire')
                    ->description('Informations de base du propriétaire')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Adresse email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Photo de profil')
                            ->image()
                            ->directory('avatars')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Compte')
                    ->schema([
                        Forms\Components\Hidden::make('role')
                            ->default('owner')
                            ->dehydrated(fn (string $context): bool => $context === 'create'),
                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->helperText('Laissez vide pour conserver le mot de passe actuel'),
                    ])->columns(2),

                Forms\Components\Section::make('Vérifications')
                    ->description('Statut de vérification du propriétaire')
                    ->schema([
                        Forms\Components\Toggle::make('email_verified')
                            ->label('Email vérifié'),
                        Forms\Components\Toggle::make('phone_verified')
                            ->label('Téléphone vérifié'),
                        Forms\Components\Toggle::make('identity_verified')
                            ->label('Identité vérifiée'),
                        Forms\Components\Select::make('verification_level')
                            ->label('Niveau de vérification')
                            ->options([
                                0 => 'Non vérifié',
                                1 => 'Email vérifié',
                                2 => 'Téléphone vérifié',
                                3 => 'Identité vérifiée',
                            ])
                            ->default(0),
                    ])->columns(4),

                Forms\Components\Section::make('Finances')
                    ->schema([
                        Forms\Components\Placeholder::make('referral_balance')
                            ->label('Solde parrainage')
                            ->content(fn ($record) => $record ? number_format($record->referral_balance ?? 0, 0, ',', ' ').' FCFA' : '0 FCFA'),
                        Forms\Components\Placeholder::make('residences_count')
                            ->label('Nombre d\'annonces')
                            ->content(fn ($record) => $record?->residences()->count() ?? 0),
                        Forms\Components\Placeholder::make('bookings_count')
                            ->label('Réservations reçues')
                            ->content(fn ($record) => $record?->ownerBookings()->count() ?? 0),
                    ])->columns(3)
                    ->visibleOn('edit'),

                Forms\Components\Section::make('Suspension')
                    ->schema([
                        Forms\Components\Toggle::make('is_suspended')
                            ->label('Compte suspendu')
                            ->live()
                            ->helperText('Suspendre temporairement ce propriétaire'),
                        Forms\Components\DateTimePicker::make('suspended_until')
                            ->label('Suspendu jusqu\'au')
                            ->visible(fn ($get) => $get('is_suspended')),
                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Raison de la suspension')
                            ->visible(fn ($get) => $get('is_suspended'))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=10b981&color=fff'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->placeholder('Non renseigné'),
                Tables\Columns\TextColumn::make('residences_count')
                    ->label('Annonces')
                    ->counts('residences')
                    ->badge()
                    ->color('success'),
                Tables\Columns\IconColumn::make('identity_verified')
                    ->label('Vérifié')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning'),
                Tables\Columns\IconColumn::make('is_suspended')
                    ->label('Statut')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn ($record) => $record->is_suspended ? 'Compte suspendu' : 'Compte actif'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('identity_verified')
                    ->label('Identité vérifiée'),
                Tables\Filters\TernaryFilter::make('is_suspended')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Suspendus')
                    ->falseLabel('Actifs'),
                Tables\Filters\Filter::make('has_residences')
                    ->label('Avec annonces')
                    ->query(fn (Builder $query) => $query->has('residences')),
                Tables\Filters\Filter::make('no_residences')
                    ->label('Sans annonces')
                    ->query(fn (Builder $query) => $query->doesntHave('residences')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\Action::make('view_residences')
                    ->label('Annonces')
                    ->icon('heroicon-o-home')
                    ->color('info')
                    ->url(fn ($record) => ResidenceResource::getUrl('index', ['tableFilters[owner_id][value]' => $record->id])),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspendre')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => !$record->is_suspended)
                    ->requiresConfirmation()
                    ->modalHeading('Suspendre ce propriétaire ?')
                    ->modalDescription('Le propriétaire ne pourra plus accéder à son compte et ses annonces seront masquées.')
                    ->form([
                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Raison de la suspension')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'is_suspended' => true,
                            'suspension_reason' => $data['suspension_reason'],
                        ]);
                    }),
                Tables\Actions\Action::make('unsuspend')
                    ->label('Réactiver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->is_suspended)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'is_suspended' => false,
                        'suspended_until' => null,
                        'suspension_reason' => null,
                    ])),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(UserExporter::class)
                    ->label('Exporter CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ])
            ->emptyStateHeading('Aucun propriétaire')
            ->emptyStateDescription('Les propriétaires s\'inscrivent via le site ou vous pouvez en créer un.')
            ->emptyStateIcon('heroicon-o-home-modern');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOwners::route('/'),
            'create' => Pages\CreateOwner::route('/create'),
            'edit' => Pages\EditOwner::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
