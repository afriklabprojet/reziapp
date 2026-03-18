<?php

namespace App\Filament\Resources;

use App\Filament\Exports\ResidenceExporter;
use App\Filament\Resources\ResidenceResource\Pages;
use App\Models\City;
use App\Models\CommuneList;
use App\Models\Country;
use App\Models\Residence;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResidenceResource extends Resource
{
    protected static ?string $model = Residence::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Gestion des annonces';

    protected static ?string $navigationLabel = 'Résidences';

    protected static ?string $modelLabel = 'Résidence';

    protected static ?string $pluralModelLabel = 'Résidences';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    // L'admin ne peut pas créer de résidence, seuls les propriétaires le peuvent
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Résidence')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informations générales')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(4)
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('owner_id')
                                    ->label('Propriétaire')
                                    ->relationship('owner', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('category_id')
                                    ->label('Catégorie')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('type')
                                    ->label('Type de logement')
                                    ->options([
                                        'apartment' => 'Appartement',
                                        'house' => 'Maison',
                                        'villa' => 'Villa',
                                        'studio' => 'Studio',
                                        'room' => 'Chambre',
                                    ])
                                    ->required(),
                                Forms\Components\Textarea::make('house_rules')
                                    ->label('Règlement intérieur')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Localisation')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\TextInput::make('address')
                                    ->label('Adresse')
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('country_code')
                                    ->label('Pays')
                                    ->options(Country::active()->pluck('name', 'code'))
                                    ->default('CI')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('city', null);
                                        $set('commune', null);
                                    }),
                                Forms\Components\Select::make('city')
                                    ->label('Ville')
                                    ->options(function (Get $get) {
                                        $code = $get('country_code');
                                        if (! $code) return [];
                                        $country = Country::where('code', $code)->first();
                                        if (! $country) return [];
                                        return City::where('country_id', $country->id)
                                            ->active()->ordered()
                                            ->pluck('name', 'name');
                                    })
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('commune', null)),
                                Forms\Components\Select::make('commune')
                                    ->label('Commune')
                                    ->options(function (Get $get) {
                                        $cityName = $get('city');
                                        $code = $get('country_code');
                                        if (! $cityName || ! $code) return [];
                                        $country = Country::where('code', $code)->first();
                                        if (! $country) return [];
                                        $city = City::where('country_id', $country->id)
                                            ->where('name', $cityName)->first();
                                        if (! $city) return [];
                                        $communes = CommuneList::where('city_id', $city->id)
                                            ->active()->pluck('name', 'name');
                                        return $communes->isNotEmpty() ? $communes : [];
                                    })
                                    ->searchable()
                                    ->required(),
                                Forms\Components\TextInput::make('quartier')
                                    ->label('Quartier'),
                                Forms\Components\TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->numeric(),
                                Forms\Components\TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->numeric(),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Caractéristiques')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Forms\Components\TextInput::make('bedrooms')
                                    ->label('Chambres')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0),
                                Forms\Components\TextInput::make('bathrooms')
                                    ->label('Salles de bain')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0),
                                Forms\Components\TextInput::make('max_guests')
                                    ->label('Voyageurs max')
                                    ->numeric()
                                    ->default(2)
                                    ->minValue(1),
                                Forms\Components\TextInput::make('surface_area')
                                    ->label('Surface (m²)')
                                    ->numeric()
                                    ->suffix('m²'),
                                Forms\Components\TimePicker::make('check_in_time')
                                    ->label('Heure d\'arrivée')
                                    ->default('14:00'),
                                Forms\Components\TimePicker::make('check_out_time')
                                    ->label('Heure de départ')
                                    ->default('11:00'),
                            ])->columns(3),

                        Forms\Components\Tabs\Tab::make('Tarification')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\TextInput::make('price_per_day')
                                    ->label('Prix par jour')
                                    ->numeric()
                                    ->required()
                                    ->prefix('FCFA'),
                                Forms\Components\TextInput::make('price_per_week')
                                    ->label('Prix par semaine')
                                    ->numeric()
                                    ->prefix('FCFA'),
                                Forms\Components\TextInput::make('price_per_month')
                                    ->label('Prix par mois')
                                    ->numeric()
                                    ->prefix('FCFA'),
                                Forms\Components\TextInput::make('min_nights')
                                    ->label('Nuits minimum')
                                    ->numeric()
                                    ->default(1),
                                Forms\Components\TextInput::make('max_nights')
                                    ->label('Nuits maximum')
                                    ->numeric()
                                    ->default(30),
                            ])->columns(3),

                        Forms\Components\Tabs\Tab::make('Statut')
                            ->icon('heroicon-o-check-badge')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'draft' => 'Brouillon',
                                        'pending' => 'En attente de validation',
                                        'needs_changes' => 'Modifications requises',
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        'rejected' => 'Rejetée',
                                    ])
                                    ->default('pending')
                                    ->required(),
                                Forms\Components\Toggle::make('is_verified')
                                    ->label('Vérifiée'),
                                Forms\Components\Toggle::make('is_top_residence')
                                    ->label('Résidence premium'),
                                Forms\Components\Toggle::make('instant_book')
                                    ->label('Réservation instantanée'),
                                Forms\Components\Toggle::make('is_available')
                                    ->label('Disponible')
                                    ->default(true),
                                Forms\Components\Toggle::make('is_suspended')
                                    ->label('Suspendue'),
                                Forms\Components\Textarea::make('suspension_reason')
                                    ->label('Raison de suspension')
                                    ->visible(fn ($get) => $get('is_suspended'))
                                    ->columnSpanFull(),
                            ])->columns(3),

                        Forms\Components\Tabs\Tab::make('Modération')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Placeholder::make('moderation_status')
                                    ->label('Historique de modération')
                                    ->content(fn ($record) => $record?->moderated_at
                                        ? 'Modéré le '.$record->moderated_at->format('d/m/Y à H:i').' par '.($record->moderator?->name ?? 'Inconnu')
                                        : 'Pas encore modéré')
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('rejection_reason')
                                    ->label('Motif de rejet')
                                    ->rows(2)
                                    ->disabled()
                                    ->visible(fn ($record) => $record?->status === 'rejected'),
                                Forms\Components\Textarea::make('changes_requested')
                                    ->label('Modifications demandées')
                                    ->rows(2)
                                    ->disabled()
                                    ->visible(fn ($record) => $record?->status === 'needs_changes'),
                                Forms\Components\Textarea::make('moderation_notes')
                                    ->label('Notes de modération (internes)')
                                    ->rows(3)
                                    ->helperText('Ces notes ne sont visibles que par les administrateurs')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('approval_score')
                                    ->label('Score de qualité')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('/100')
                                    ->helperText('Score attribué à l\'annonce (0-100)'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Accessibilité')
                            ->icon('heroicon-o-hand-raised')
                            ->schema([
                                Forms\Components\Toggle::make('is_accessible')
                                    ->label('Accessible PMR'),
                                Forms\Components\Textarea::make('accessibility_features')
                                    ->label('Équipements d\'accessibilité')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('commune')
                    ->label('Commune')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('country_code')
                    ->label('Pays')
                    ->badge()
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) return 'CI';
                        $country = Country::where('code', $state)->first();
                        return $country ? "{$country->flag_emoji} {$state}" : $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'apartment' => 'Appartement',
                        'house' => 'Maison',
                        'villa' => 'Villa',
                        'studio' => 'Studio',
                        'room' => 'Chambre',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('price_per_day')
                    ->label('Prix/jour')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'needs_changes' => 'info',
                        'draft' => 'gray',
                        'inactive' => 'gray',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match($state) {
                        'active' => 'heroicon-m-check-circle',
                        'pending' => 'heroicon-m-clock',
                        'needs_changes' => 'heroicon-m-pencil-square',
                        'draft' => 'heroicon-m-document',
                        'inactive' => 'heroicon-m-pause-circle',
                        'rejected' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'draft' => 'Brouillon',
                        'pending' => 'En attente',
                        'needs_changes' => 'À modifier',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'rejected' => 'Rejetée',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Vérifiée')
                    ->boolean(),
                Tables\Columns\IconColumn::make('instant_book')
                    ->label('Instant')
                    ->boolean(),
                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Note')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).' ⭐' : '-'),
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Vues')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'pending' => 'En attente',
                        'needs_changes' => 'Modifications requises',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'rejected' => 'Rejetée',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'apartment' => 'Appartement',
                        'house' => 'Maison',
                        'villa' => 'Villa',
                        'studio' => 'Studio',
                        'room' => 'Chambre',
                    ]),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Vérifiée'),
                Tables\Filters\TernaryFilter::make('instant_book')
                    ->label('Réservation instantanée'),
                Tables\Filters\TernaryFilter::make('is_suspended')
                    ->label('Suspendue'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Voir'),
                    Tables\Actions\EditAction::make()->label('Modifier'),

                    // Actions de modération
                    Tables\Actions\Action::make('approve')
                        ->label('Approuver')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'needs_changes']))
                        ->form([
                            Forms\Components\TextInput::make('approval_score')
                                ->label('Score de qualité')
                                ->numeric()
                                ->default(80)
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('/100'),
                            Forms\Components\Textarea::make('moderation_notes')
                                ->label('Notes (optionnel)')
                                ->rows(2),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => 'active',
                                'is_verified' => true,
                                'verified_at' => now(),
                                'moderated_by' => auth()->id(),
                                'moderated_at' => now(),
                                'approval_score' => $data['approval_score'] ?? 80,
                                'moderation_notes' => $data['moderation_notes'] ?? null,
                                'rejection_reason' => null,
                                'changes_requested' => null,
                            ]);

                            Notification::make()
                                ->title('Annonce approuvée')
                                ->body("La résidence \"{$record->name}\" a été approuvée et publiée.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('request_changes')
                        ->label('Demander modifications')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->form([
                            Forms\Components\Textarea::make('changes_requested')
                                ->label('Modifications à apporter')
                                ->required()
                                ->rows(4)
                                ->helperText('Décrivez les modifications que le propriétaire doit apporter'),
                            Forms\Components\Textarea::make('moderation_notes')
                                ->label('Notes internes (optionnel)')
                                ->rows(2),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => 'needs_changes',
                                'changes_requested' => $data['changes_requested'],
                                'moderated_by' => auth()->id(),
                                'moderated_at' => now(),
                                'moderation_notes' => $data['moderation_notes'] ?? null,
                            ]);

                            Notification::make()
                                ->title('Modifications demandées')
                                ->body("Des modifications ont été demandées pour \"{$record->name}\".")
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\Action::make('reject')
                        ->label('Rejeter')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'needs_changes']))
                        ->requiresConfirmation()
                        ->modalHeading('Rejeter cette annonce')
                        ->modalDescription('Cette action notifiera le propriétaire du rejet de son annonce.')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Motif du rejet')
                                ->required()
                                ->rows(3),
                            Forms\Components\Textarea::make('moderation_notes')
                                ->label('Notes internes (optionnel)')
                                ->rows(2),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => 'rejected',
                                'rejection_reason' => $data['rejection_reason'],
                                'moderated_by' => auth()->id(),
                                'moderated_at' => now(),
                                'moderation_notes' => $data['moderation_notes'] ?? null,
                            ]);

                            Notification::make()
                                ->title('Annonce rejetée')
                                ->body("L'annonce \"{$record->name}\" a été rejetée.")
                                ->danger()
                                ->send();
                        }),

                    // Actions de gestion
                    Tables\Actions\Action::make('toggle_premium')
                        ->label(fn ($record) => $record->is_top_residence ? 'Retirer premium' : 'Rendre premium')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'active')
                        ->action(function ($record) {
                            $record->update(['is_top_residence' => !$record->is_top_residence]);

                            Notification::make()
                                ->title($record->is_top_residence ? 'Premium activé' : 'Premium désactivé')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('suspend')
                        ->label('Suspendre')
                        ->icon('heroicon-o-pause-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->status === 'active' && !$record->is_suspended)
                        ->form([
                            Forms\Components\Textarea::make('suspension_reason')
                                ->label('Raison de la suspension')
                                ->required()
                                ->rows(2),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'is_suspended' => true,
                                'status' => 'inactive',
                                'suspension_reason' => $data['suspension_reason'],
                            ]);

                            Notification::make()
                                ->title('Annonce suspendue')
                                ->body("L'annonce \"{$record->name}\" a été suspendue.")
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\Action::make('reactivate')
                        ->label('Réactiver')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->is_suspended || $record->status === 'inactive')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'is_suspended' => false,
                                'status' => 'active',
                                'suspension_reason' => null,
                            ]);

                            Notification::make()
                                ->title('Annonce réactivée')
                                ->success()
                                ->send();
                        }),
                ])->dropdown(true)
                    ->dropdownWidth(\Filament\Support\Enums\MaxWidth::ExtraSmall),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ResidenceExporter::class)
                    ->label('Exporter CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Approuver la sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (in_array($record->status, ['pending', 'needs_changes'])) {
                                    $record->update([
                                        'status' => 'active',
                                        'is_verified' => true,
                                        'verified_at' => now(),
                                        'moderated_by' => auth()->id(),
                                        'moderated_at' => now(),
                                        'approval_score' => 80,
                                    ]);
                                    $count++;
                                }
                            }

                            // Log activity
                            \App\Models\AdminActivityLog::log(
                                \App\Models\AdminActivityLog::ACTION_BULK_ACTION,
                                "{$count} résidences approuvées en masse",
                                null,
                                null,
                                ['action' => 'approve', 'count' => $count, 'ids' => $records->pluck('id')->toArray()]
                            );

                            Notification::make()
                                ->title("{$count} annonces approuvées")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('bulk_reject')
                        ->label('Rejeter la sélection')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Motif du rejet')
                                ->required(),
                        ])
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (in_array($record->status, ['pending', 'needs_changes'])) {
                                    $record->update([
                                        'status' => 'rejected',
                                        'rejection_reason' => $data['rejection_reason'],
                                        'moderated_by' => auth()->id(),
                                        'moderated_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }

                            // Log activity
                            \App\Models\AdminActivityLog::log(
                                \App\Models\AdminActivityLog::ACTION_BULK_ACTION,
                                "{$count} résidences rejetées en masse: {$data['rejection_reason']}",
                                null,
                                null,
                                ['action' => 'reject', 'count' => $count, 'reason' => $data['rejection_reason']]
                            );

                            Notification::make()
                                ->title("{$count} annonces rejetées")
                                ->danger()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('bulk_suspend')
                        ->label('Suspendre la sélection')
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->form([
                            Forms\Components\Textarea::make('suspension_reason')
                                ->label('Motif de suspension')
                                ->required(),
                        ])
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'active') {
                                    $record->update([
                                        'is_suspended' => true,
                                        'suspension_reason' => $data['suspension_reason'],
                                        'suspended_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }

                            \App\Models\AdminActivityLog::log(
                                \App\Models\AdminActivityLog::ACTION_BULK_ACTION,
                                "{$count} résidences suspendues: {$data['suspension_reason']}",
                                null,
                                null,
                                ['action' => 'suspend', 'count' => $count]
                            );

                            Notification::make()
                                ->title("{$count} annonces suspendues")
                                ->warning()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informations générales')
                    ->icon('heroicon-o-home')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nom')
                            ->weight(FontWeight::Bold)
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        Infolists\Components\TextEntry::make('owner.name')
                            ->label('Propriétaire')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('category.name')
                            ->label('Catégorie')
                            ->badge(),
                        Infolists\Components\TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'apartment' => 'Appartement',
                                'house' => 'Maison',
                                'villa' => 'Villa',
                                'studio' => 'Studio',
                                'room' => 'Chambre',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->prose(),
                    ])->columns(4),

                Infolists\Components\Section::make('Localisation')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Infolists\Components\TextEntry::make('address')
                            ->label('Adresse')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('commune')
                            ->label('Commune'),
                        Infolists\Components\TextEntry::make('quartier')
                            ->label('Quartier'),
                        Infolists\Components\TextEntry::make('latitude')
                            ->label('Latitude'),
                        Infolists\Components\TextEntry::make('longitude')
                            ->label('Longitude'),
                    ])->columns(4),

                Infolists\Components\Section::make('Caractéristiques')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        Infolists\Components\TextEntry::make('bedrooms')
                            ->label('Chambres')
                            ->icon('heroicon-o-home'),
                        Infolists\Components\TextEntry::make('bathrooms')
                            ->label('Salles de bain'),
                        Infolists\Components\TextEntry::make('max_guests')
                            ->label('Voyageurs max')
                            ->icon('heroicon-o-user-group'),
                        Infolists\Components\TextEntry::make('surface_area')
                            ->label('Surface')
                            ->suffix(' m²'),
                    ])->columns(4),

                Infolists\Components\Section::make('Tarification')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Infolists\Components\TextEntry::make('price_per_day')
                            ->label('Prix/jour')
                            ->money('XOF'),
                        Infolists\Components\TextEntry::make('price_per_week')
                            ->label('Prix/semaine')
                            ->money('XOF'),
                        Infolists\Components\TextEntry::make('price_per_month')
                            ->label('Prix/mois')
                            ->money('XOF'),
                    ])->columns(3),

                Infolists\Components\Section::make('Statut & Modération')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'active' => 'success',
                                'pending' => 'warning',
                                'needs_changes' => 'info',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\IconEntry::make('is_verified')
                            ->label('Vérifiée')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('is_top_residence')
                            ->label('Premium')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('instant_book')
                            ->label('Réservation instantanée')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('moderator.name')
                            ->label('Modéré par')
                            ->placeholder('Non modéré'),
                        Infolists\Components\TextEntry::make('moderated_at')
                            ->label('Date modération')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non modéré'),
                        Infolists\Components\TextEntry::make('approval_score')
                            ->label('Score qualité')
                            ->suffix('/100')
                            ->default('-'),
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('Motif de rejet')
                            ->visible(fn ($record) => $record->status === 'rejected')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('changes_requested')
                            ->label('Modifications demandées')
                            ->visible(fn ($record) => $record->status === 'needs_changes')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('moderation_notes')
                            ->label('Notes de modération')
                            ->visible(fn ($record) => !empty($record->moderation_notes))
                            ->columnSpanFull(),
                    ])->columns(4),

                Infolists\Components\Section::make('Statistiques')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Infolists\Components\TextEntry::make('views_count')
                            ->label('Vues'),
                        Infolists\Components\TextEntry::make('average_rating')
                            ->label('Note moyenne')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).' ⭐' : '-'),
                        Infolists\Components\TextEntry::make('reviews_count')
                            ->label('Avis'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Créée le')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(4),
            ]);
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
            'index' => Pages\ListResidences::route('/'),
            'create' => Pages\CreateResidence::route('/create'),
            'view' => Pages\ViewResidence::route('/{record}'),
            'edit' => Pages\EditResidence::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['owner', 'category', 'moderator']);
    }
}
