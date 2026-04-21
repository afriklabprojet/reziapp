<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerBadgeResource\Pages;
use App\Models\OwnerBadge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\HtmlString;

class OwnerBadgeResource extends Resource
{
    protected static ?string $model = OwnerBadge::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Propriétaires';

    protected static ?string $navigationLabel = 'Badges de confiance';

    protected static ?string $modelLabel = 'Badge';

    protected static ?string $pluralModelLabel = 'Badges de confiance';

    protected static ?int $navigationSort = 3;

    // Les badges sont auto-générés, pas de création manuelle
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du badge')
                    ->schema([
                        Forms\Components\TextInput::make('owner.name')
                            ->label('Propriétaire')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('badge_name')
                            ->label('Badge')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'active' => '✅ Actif',
                                'pending' => '⏳ En attente',
                                'suspended' => '⚠️ Suspendu',
                                'revoked' => '❌ Révoqué',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Raison')
                            ->rows(2)
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DatePicker::make('earned_at')
                            ->label('Attribué le')
                            ->disabled(),

                        Forms\Components\DatePicker::make('expires_at')
                            ->label('Expire le')
                            ->helperText('Modifiable pour prolonger ou réduire la validité'),
                    ])->columns(2),

                Forms\Components\Section::make('Métriques (calculées automatiquement)')
                    ->schema([
                        Forms\Components\Placeholder::make('metrics_display')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record || !$record->metadata) {
                                    return 'Aucune métrique disponible';
                                }

                                $html = '<div class="grid grid-cols-2 gap-4">';
                                $labels = [
                                    'avg_rating' => '⭐ Note moyenne',
                                    'reviews_count' => '📝 Nombre d\'avis',
                                    'completed_bookings' => '✅ Réservations complétées',
                                    'cancellation_rate' => '❌ Taux d\'annulation',
                                    'response_time_minutes' => '⚡ Temps de réponse (min)',
                                    'response_rate' => '📨 Taux de réponse',
                                    'active_listings' => '🏠 Annonces actives',
                                    'account_age_months' => '📅 Ancienneté (mois)',
                                    'identity_verified' => '🪪 Identité vérifiée',
                                    'phone_verified' => '📱 Téléphone vérifié',
                                ];

                                foreach ($record->metadata as $key => $value) {
                                    $label = $labels[$key] ?? $key;
                                    $displayValue = is_bool($value) ? ($value ? 'Oui' : 'Non') : $value;
                                    if ($key === 'cancellation_rate' || $key === 'response_rate') {
                                        $displayValue = round($value * 100).'%';
                                    }
                                    $html .= "<div><strong>{$label}:</strong> {$displayValue}</div>";
                                }
                                $html .= '</div>';

                                return new HtmlString($html);
                            }),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire')
                    ->sortable()
                    ->searchable()
                    ->url(fn ($record) => route('filament.admin.resources.users.edit', $record->user_id)),

                Tables\Columns\TextColumn::make('badge_type')
                    ->label('Badge')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'verified_identity' => 'info',
                        'verified_phone' => 'success',
                        'verified_residence' => 'purple',
                        'superhost' => 'warning',
                        'trusted' => 'orange',
                        'responsive' => 'cyan',
                        'top_rated' => 'amber',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'verified_identity' => '🪪 Identité vérifiée',
                        'verified_phone' => '📱 Téléphone vérifié',
                        'verified_residence' => '🏠 Logement vérifié',
                        'superhost' => '🏆 Superhôte',
                        'trusted' => '⭐ Hôte de confiance',
                        'responsive' => '⚡ Réponse rapide',
                        'top_rated' => '🥇 Top noté',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'suspended' => 'orange',
                        'revoked' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Actif',
                        'pending' => 'En attente',
                        'suspended' => 'Suspendu',
                        'revoked' => 'Révoqué',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('metadata.avg_rating')
                    ->label('Note')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).' ⭐' : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('earned_at')
                    ->label('Attribué le')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expire le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at && $record->expires_at->isPast() ? 'danger' : null)
                    ->placeholder('Permanent'),

                Tables\Columns\IconColumn::make('is_auto')
                    ->label('Auto')
                    ->state(fn ($record) => str_contains($record->reason ?? '', 'automatiquement'))
                    ->boolean()
                    ->trueIcon('heroicon-o-cpu-chip')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('info')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('badge_type')
                    ->label('Type de badge')
                    ->options([
                        'verified_identity' => '🪪 Identité vérifiée',
                        'verified_phone' => '📱 Téléphone vérifié',
                        'verified_residence' => '🏠 Logement vérifié',
                        'superhost' => '🏆 Superhôte',
                        'trusted' => '⭐ Hôte de confiance',
                        'responsive' => '⚡ Réponse rapide',
                        'top_rated' => '🥇 Top noté',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'pending' => 'En attente',
                        'suspended' => 'Suspendu',
                        'revoked' => 'Révoqué',
                    ]),

                Tables\Filters\Filter::make('expires_soon')
                    ->label('Expire bientôt')
                    ->query(fn ($query) => $query->where('expires_at', '<=', now()->addDays(30))->where('expires_at', '>', now())),

                Tables\Filters\Filter::make('expired')
                    ->label('Expirés')
                    ->query(fn ($query) => $query->where('expires_at', '<', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->label('Modifier'),

                Tables\Actions\Action::make('recalculate_user')
                    ->label('Recalculer')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Recalculer les badges')
                    ->modalDescription('Recalculer tous les badges de ce propriétaire selon les critères actuels?')
                    ->action(function (OwnerBadge $record) {
                        Artisan::call('rezi:calculate-owner-badges', ['--user' => $record->user_id]);

                        Notification::make()
                            ->title('Badges recalculés')
                            ->body("Les badges de {$record->owner->name} ont été recalculés.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('revoke')
                    ->label('Révoquer')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (OwnerBadge $record) => $record->status === 'active')
                    ->form([
                        Forms\Components\Textarea::make('revoke_reason')
                            ->label('Raison de la révocation')
                            ->required(),
                    ])
                    ->action(function (OwnerBadge $record, array $data) {
                        $record->update([
                            'status' => 'revoked',
                            'reason' => 'Révoqué manuellement: '.$data['revoke_reason'],
                        ]);

                        Notification::make()
                            ->title('Badge révoqué')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('recalculate_all')
                    ->label('Recalculer tous les badges')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Recalculer tous les badges')
                    ->modalDescription('Cette action va analyser tous les propriétaires et attribuer/révoquer les badges selon leurs métriques actuelles. Continuer?')
                    ->modalSubmitActionLabel('Recalculer')
                    ->action(function () {
                        try {
                            Artisan::call('rezi:calculate-owner-badges');
                            $output = Artisan::output();

                            Notification::make()
                                ->title('Badges recalculés')
                                ->body('Tous les badges ont été recalculés avec succès.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur')
                                ->body('Erreur lors du recalcul: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_revoke')
                        ->label('Révoquer la sélection')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'active') {
                                    $record->update([
                                        'status' => 'revoked',
                                        'reason' => 'Révoqué en masse par admin',
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("{$count} badges révoqués")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('earned_at', 'desc')
            ->emptyStateHeading('Aucun badge attribué')
            ->emptyStateDescription('Cliquez sur "Recalculer tous les badges" pour analyser les propriétaires et attribuer les badges automatiquement.')
            ->emptyStateIcon('heroicon-o-shield-check');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOwnerBadges::route('/'),
            'view' => Pages\ViewOwnerBadge::route('/{record}'),
            'edit' => Pages\EditOwnerBadge::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $expiring = static::getModel()::where('status', 'active')
            ->where('expires_at', '<=', now()->addDays(7))
            ->where('expires_at', '>', now())
            ->count();

        return $expiring > 0 ? "{$expiring} expire" : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
