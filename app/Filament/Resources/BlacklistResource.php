<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlacklistResource\Pages;
use App\Models\Blacklist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BlacklistResource extends Resource
{
    protected static ?string $model = Blacklist::class;

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';

    protected static ?string $navigationGroup = 'Sécurité';

    protected static ?string $navigationLabel = 'Blacklist';

    protected static ?string $modelLabel = 'Entrée blacklist';

    protected static ?string $pluralModelLabel = 'Blacklist';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cible')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'user' => 'Utilisateur',
                                'email' => 'Email',
                                'phone' => 'Téléphone',
                                'ip' => 'Adresse IP',
                                'device' => 'Appareil',
                                'document' => 'Document',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('value')
                            ->label('Valeur (email, téléphone, IP…)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur associé')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(3),

                Forms\Components\Section::make('Motif')
                    ->schema([
                        Forms\Components\Select::make('reason')
                            ->label('Raison')
                            ->options([
                                'fraud' => 'Fraude',
                                'scam' => 'Arnaque',
                                'harassment' => 'Harcèlement',
                                'spam' => 'Spam',
                                'fake_identity' => 'Fausse identité',
                                'payment_default' => 'Défaut de paiement',
                                'terms_violation' => 'Violation CGU',
                                'legal_request' => 'Demande légale',
                                'other' => 'Autre',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('restriction_level')
                            ->label('Niveau de restriction')
                            ->options([
                                'warning' => 'Avertissement',
                                'limited' => 'Fonctionnalités limitées',
                                'suspended' => 'Suspendu',
                                'banned' => 'Banni',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Durée')
                    ->schema([
                        Forms\Components\Toggle::make('is_permanent')
                            ->label('Permanent')
                            ->default(true)
                            ->live(),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expire le')
                            ->visible(fn (callable $get) => !$get('is_permanent'))
                            ->after('today'),
                        Forms\Components\Toggle::make('appeal_allowed')
                            ->label('Appel autorisé')
                            ->default(true),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Appel')
                    ->schema([
                        Forms\Components\Select::make('appeal_status')
                            ->label('Statut de l\'appel')
                            ->options([
                                'none' => 'Aucun',
                                'pending' => 'En attente',
                                'approved' => 'Approuvé',
                                'rejected' => 'Rejeté',
                            ])
                            ->default('none')
                            ->disabled(),
                        Forms\Components\Textarea::make('appeal_message')
                            ->label('Message d\'appel')
                            ->rows(2)
                            ->disabled(),
                        Forms\Components\Textarea::make('appeal_response')
                            ->label('Réponse à l\'appel')
                            ->rows(2),
                    ])->columns(1)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'user' => 'Utilisateur',
                        'email' => 'Email',
                        'phone' => 'Téléphone',
                        'ip' => 'IP',
                        'device' => 'Appareil',
                        'document' => 'Document',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Raison')
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fraud' => 'Fraude',
                        'scam' => 'Arnaque',
                        'harassment' => 'Harcèlement',
                        'spam' => 'Spam',
                        'fake_identity' => 'Fausse identité',
                        'payment_default' => 'Défaut paiement',
                        'terms_violation' => 'Violation CGU',
                        'legal_request' => 'Demande légale',
                        'other' => 'Autre',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('restriction_level')
                    ->label('Restriction')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'warning' => 'warning',
                        'limited' => 'info',
                        'suspended' => 'danger',
                        'banned' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'warning' => 'Avertissement',
                        'limited' => 'Limité',
                        'suspended' => 'Suspendu',
                        'banned' => 'Banni',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_permanent')
                    ->label('Permanent')
                    ->boolean(),
                Tables\Columns\TextColumn::make('appeal_status')
                    ->label('Appel')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'none', null => '—',
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expire le')
                    ->dateTime('d/m/Y')
                    ->placeholder('Jamais')
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Créé par')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'user' => 'Utilisateur',
                        'email' => 'Email',
                        'phone' => 'Téléphone',
                        'ip' => 'IP',
                        'device' => 'Appareil',
                        'document' => 'Document',
                    ]),
                Tables\Filters\SelectFilter::make('restriction_level')
                    ->label('Restriction')
                    ->options([
                        'warning' => 'Avertissement',
                        'limited' => 'Limité',
                        'suspended' => 'Suspendu',
                        'banned' => 'Banni',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
                Tables\Filters\SelectFilter::make('appeal_status')
                    ->label('Appel')
                    ->options([
                        'none' => 'Aucun',
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),

                // Désactiver
                Tables\Actions\Action::make('deactivate')
                    ->label('Désactiver')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Désactiver cette entrée ?')
                    ->modalDescription('L\'utilisateur retrouvera l\'accès à la plateforme.')
                    ->visible(fn (Blacklist $record): bool => $record->is_active)
                    ->action(function (Blacklist $record): void {
                        $record->deactivate(Auth::id());

                        if ($record->user_id) {
                            $record->user?->update([
                                'is_suspended' => false,
                                'suspension_reason' => null,
                            ]);
                        }
                    }),

                // Approuver l'appel
                Tables\Actions\Action::make('approve_appeal')
                    ->label('Approuver appel')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Blacklist $record): bool => $record->appeal_status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('response')
                            ->label('Réponse')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (Blacklist $record, array $data): void {
                        $record->approveAppeal(Auth::id(), $data['response']);
                    }),

                // Rejeter l'appel
                Tables\Actions\Action::make('reject_appeal')
                    ->label('Rejeter appel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Blacklist $record): bool => $record->appeal_status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('response')
                            ->label('Motif du rejet')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (Blacklist $record, array $data): void {
                        $record->rejectAppeal(Auth::id(), $data['response']);
                    }),
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
            'index' => Pages\ListBlacklists::route('/'),
            'create' => Pages\CreateBlacklist::route('/create'),
            'edit' => Pages\EditBlacklist::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /**
     * Mutate data before create — auto-set created_by
     */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
