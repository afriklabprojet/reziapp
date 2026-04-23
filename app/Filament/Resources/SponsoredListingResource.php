<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SponsoredListingResource\Pages;
use App\Models\SponsoredListing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SponsoredListingResource extends Resource
{
    protected static ?string $model = SponsoredListing::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Mise en avant';

    protected static ?string $modelLabel = 'Mise en avant';

    protected static ?string $pluralModelLabel = 'Mises en avant';

    protected static ?int $navigationSort = 5;

    // ── Badge de navigation (campagnes en attente) ──────────────
    public static function getNavigationBadge(): ?string
    {
        $count = SponsoredListing::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ── Formulaire ──────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Annonce')
                    ->icon('heroicon-o-home')
                    ->schema([
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Propriétaire')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Type et période')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type de sponsoring')
                            ->options([
                                'featured_home' => '🏠 Page d\'accueil',
                                'top_search' => '🔍 Top recherche',
                                'highlighted' => '⚡ Mis en avant',
                                'premium_listing' => '⭐ Premium',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('position')
                            ->label('Position')
                            ->options([
                                'homepage_banner' => 'Bannière accueil',
                                'homepage_featured' => 'En vedette accueil',
                                'search_top' => 'Haut résultats recherche',
                                'category_featured' => 'En vedette catégorie',
                            ])
                            ->native(false),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Date de début')
                            ->required(),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Date de fin')
                            ->required()
                            ->after('starts_at'),
                    ])->columns(2),

                Forms\Components\Section::make('Budget et facturation')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\TextInput::make('daily_budget')
                            ->label('Budget quotidien')
                            ->numeric()
                            ->suffix('FCFA'),
                        Forms\Components\TextInput::make('total_budget')
                            ->label('Budget total')
                            ->numeric()
                            ->suffix('FCFA')
                            ->required(),
                        Forms\Components\Select::make('billing_type')
                            ->label('Type de facturation')
                            ->options([
                                'flat_rate' => 'Forfait fixe',
                                'per_view' => 'Par impression',
                                'per_click' => 'Par clic',
                            ])
                            ->default('flat_rate')
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('cost_per_unit')
                            ->label('Coût par unité')
                            ->numeric()
                            ->suffix('FCFA')
                            ->visible(fn (Forms\Get $get) => in_array($get('billing_type'), ['per_click', 'per_view'])),
                    ])->columns(2),

                Forms\Components\Section::make('Performance')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Forms\Components\TextInput::make('impressions')
                            ->label('Impressions')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('clicks')
                            ->label('Clics')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('contacts_generated')
                            ->label('Contacts générés')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('amount_spent')
                            ->label('Montant dépensé')
                            ->numeric()
                            ->suffix('FCFA')
                            ->default(0),
                    ])->columns(4)
                    ->collapsible()
                    ->collapsed(fn (?SponsoredListing $record) => $record === null),

                Forms\Components\Section::make('Statut et paiement')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => '🟡 En attente',
                                'active' => '🟢 Actif',
                                'paused' => '⏸️ En pause',
                                'completed' => '✅ Terminé',
                                'cancelled' => '❌ Annulé',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Payé')
                            ->default(false)
                            ->inline(false),
                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Référence paiement')
                            ->maxLength(100),
                    ])->columns(3),
            ]);
    }

    // ── Table ───────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('residence.name')
                    ->label('Résidence')
                    ->limit(22)
                    ->tooltip(fn ($record) => $record->residence?->name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Propriétaire')
                    ->limit(15)
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'premium_listing' => 'warning',
                        'featured_home' => 'info',
                        'top_search' => 'primary',
                        'highlighted' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'premium_listing' => 'heroicon-m-star',
                        'featured_home' => 'heroicon-m-home',
                        'top_search' => 'heroicon-m-magnifying-glass',
                        'highlighted' => 'heroicon-m-bolt',
                        default => 'heroicon-m-bolt',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'featured_home' => 'Accueil',
                        'top_search' => 'Top Recherche',
                        'highlighted' => 'Mis en avant',
                        'premium_listing' => 'Premium',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_budget')
                    ->label('Budget')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ').' F')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_spent')
                    ->label('Dépensé')
                    ->formatStateUsing(fn ($record) => $record->total_budget > 0
                        ? number_format($record->amount_spent, 0, ',', ' ').' F ('.round(($record->amount_spent / $record->total_budget) * 100).'%)'
                        : number_format($record->amount_spent, 0, ',', ' ').' F')
                    ->color(fn ($record) => $record->total_budget > 0 && ($record->amount_spent / $record->total_budget) >= 0.9 ? 'danger' : null)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Période')
                    ->formatStateUsing(fn ($record) => $record->starts_at->format('d/m').' → '.$record->ends_at->format('d/m/y'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('impressions')
                    ->label('Vues')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('clicks')
                    ->label('Clics')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('click_rate')
                    ->label('CTR')
                    ->getStateUsing(fn ($record) => $record->click_rate.'%')
                    ->badge()
                    ->color(fn ($record) => $record->click_rate >= 3 ? 'success' : ($record->click_rate >= 1 ? 'warning' : 'gray'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('Jours')
                    ->getStateUsing(fn ($record) => $record->days_remaining > 0 ? $record->days_remaining.'j' : 'Expiré')
                    ->badge()
                    ->color(fn ($record) => $record->days_remaining > 7 ? 'success' : ($record->days_remaining > 0 ? 'warning' : 'danger'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'paused' => 'info',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-m-check-circle',
                        'pending' => 'heroicon-m-clock',
                        'paused' => 'heroicon-m-pause-circle',
                        'completed' => 'heroicon-m-flag',
                        'cancelled' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Actif',
                        'pending' => 'En attente',
                        'paused' => 'Pause',
                        'completed' => 'Terminé',
                        'cancelled' => 'Annulé',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Payé')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Méthode')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'wave' => 'info',
                        'orange' => 'warning',
                        'mtn' => 'warning',
                        'moov' => 'success',
                        'djamo' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'wave' => 'Wave',
                        'orange' => 'Orange Money',
                        'mtn' => 'MTN MoMo',
                        'moov' => 'Moov Money',
                        'djamo' => 'Djamo',
                        default => $state ?? '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Statut paiement')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'success' => 'success',
                        'processing' => 'warning',
                        'error' => 'danger',
                        'pending' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'success' => '✅ Réussi',
                        'processing' => '⏳ En cours',
                        'error' => '❌ Échoué',
                        'pending' => '⏸️ En attente',
                        default => $state ?? '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')

            // ── Filtres ──────────────────────────────────────────
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'active' => 'Actif',
                        'paused' => 'En pause',
                        'completed' => 'Terminé',
                        'cancelled' => 'Annulé',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'featured_home' => 'Page d\'accueil',
                        'top_search' => 'Top recherche',
                        'highlighted' => 'Mis en avant',
                        'premium_listing' => 'Premium',
                    ]),

                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Paiement')
                    ->trueLabel('Payé')
                    ->falseLabel('Non payé')
                    ->placeholder('Tous'),

                Tables\Filters\Filter::make('active_campaigns')
                    ->label('Campagnes en cours')
                    ->query(fn (Builder $query) => $query->where('status', 'active')->where('ends_at', '>', now()))
                    ->toggle(),

                Tables\Filters\Filter::make('budget_critical')
                    ->label('Budget critique (>90%)')
                    ->query(fn (Builder $query) => $query->whereRaw('amount_spent >= total_budget * 0.9')->where('total_budget', '>', 0))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersFormColumns(3)

            // ── Actions individuelles ────────────────────────────
            ->actions([
                Tables\Actions\ActionGroup::make([

                    Tables\Actions\Action::make('activate')
                        ->label('Activer')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Activer la campagne')
                        ->modalDescription('La campagne sera immédiatement visible par les utilisateurs.')
                        ->modalSubmitActionLabel('Oui, activer')
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'paused']) && $record->is_paid)
                        ->action(function ($record) {
                            $record->activate();
                            Notification::make()->success()->title('Campagne activée')->body('La mise en avant est maintenant active.')->send();
                        }),

                    Tables\Actions\Action::make('pause')
                        ->label('Mettre en pause')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Mettre en pause')
                        ->modalDescription('La campagne sera temporairement suspendue.')
                        ->modalSubmitActionLabel('Mettre en pause')
                        ->visible(fn ($record) => $record->status === 'active')
                        ->action(function ($record) {
                            $record->pause();
                            Notification::make()->success()->title('Campagne en pause')->send();
                        }),

                    Tables\Actions\Action::make('complete')
                        ->label('Terminer')
                        ->icon('heroicon-o-flag')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Terminer la campagne')
                        ->modalDescription('La campagne sera marquée comme terminée définitivement.')
                        ->modalSubmitActionLabel('Terminer')
                        ->visible(fn ($record) => in_array($record->status, ['active', 'paused']))
                        ->action(function ($record) {
                            $record->complete();
                            Notification::make()->success()->title('Campagne terminée')->send();
                        }),

                    Tables\Actions\Action::make('cancel')
                        ->label('Annuler')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Annuler la campagne')
                        ->modalDescription('Cette action est irréversible. La campagne sera définitivement annulée.')
                        ->modalSubmitActionLabel('Oui, annuler')
                        ->visible(fn ($record) => ! in_array($record->status, ['completed', 'cancelled']))
                        ->action(function ($record) {
                            $record->cancel();
                            Notification::make()->success()->title('Campagne annulée')->send();
                        }),

                    Tables\Actions\Action::make('mark_paid')
                        ->label('Marquer payé')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirmer le paiement')
                        ->modalDescription('Marquer manuellement cette campagne comme payée.')
                        ->modalSubmitActionLabel('Confirmer le paiement')
                        ->form([
                            Forms\Components\TextInput::make('payment_reference')
                                ->label('Référence paiement')
                                ->placeholder('Ex: TXN-123456')
                                ->required(),
                        ])
                        ->visible(fn ($record) => ! $record->is_paid)
                        ->action(function ($record, array $data) {
                            $duration = $record->duration_days ?? 7;
                            $record->update([
                                'is_paid' => true,
                                'status' => 'active',
                                'payment_reference' => $data['payment_reference'],
                                'payment_status' => 'success',
                                'paid_at' => now(),
                                'starts_at' => $record->starts_at ?? now(),
                                'ends_at' => $record->ends_at ?? now()->addDays($duration),
                            ]);
                            Notification::make()->success()->title('Paiement confirmé → Campagne activée')->body('Référence : '.$data['payment_reference'])->send();
                        }),

                    Tables\Actions\EditAction::make()
                        ->label('Modifier'),

                    Tables\Actions\DeleteAction::make()
                        ->label('Supprimer'),

                ])->dropdown(true)
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions'),
            ])

            // ── Actions en masse ─────────────────────────────────
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Activer la sélection')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Activer les campagnes sélectionnées')
                        ->modalSubmitActionLabel('Activer tout')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (in_array($record->status, ['pending', 'paused']) && $record->is_paid) {
                                    $record->activate();
                                    $count++;
                                }
                            }
                            Notification::make()->success()->title($count.' campagne(s) activée(s)')->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_pause')
                        ->label('Mettre en pause')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'active') {
                                    $record->pause();
                                    $count++;
                                }
                            }
                            Notification::make()->success()->title($count.' campagne(s) en pause')->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_cancel')
                        ->label('Annuler la sélection')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Annuler les campagnes sélectionnées ?')
                        ->modalDescription('Cette action est irréversible.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (! in_array($record->status, ['completed', 'cancelled'])) {
                                    $record->cancel();
                                    $count++;
                                }
                            }
                            Notification::make()->success()->title($count.' campagne(s) annulée(s)')->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer'),

                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSponsoredListings::route('/'),
            'create' => Pages\CreateSponsoredListing::route('/create'),
            'edit' => Pages\EditSponsoredListing::route('/{record}/edit'),
        ];
    }
}
