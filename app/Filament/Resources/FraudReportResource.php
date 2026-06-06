<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FraudReportResource\Pages;
use App\Models\FraudReport;
use App\Models\Notification;
use App\Models\Residence;
use App\Models\Review;
use App\Services\VerificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class FraudReportResource extends Resource
{
    protected static ?string $model = FraudReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Vérifications';

    protected static ?string $navigationLabel = 'Signalements fraude';

    protected static ?string $modelLabel = 'Signalement';

    protected static ?string $pluralModelLabel = 'Signalements de fraude';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\Select::make('reporter_id')
                            ->label('Signalé par')
                            ->relationship('reporter', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('target_user_id')
                            ->label('Utilisateur signalé')
                            ->relationship('targetUser', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence concernée')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('fraud_type')
                            ->label('Type de fraude')
                            ->options([
                                'fake_listing' => 'Fausse annonce',
                                'scam' => 'Arnaque',
                                'identity_theft' => 'Usurpation d\'identité',
                                'payment_fraud' => 'Fraude paiement',
                                'fake_review' => 'Faux avis',
                                'other' => 'Autre',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('evidence')
                            ->label('Preuves')
                            ->multiple()
                            ->directory('fraud-reports')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Traitement')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'investigating' => 'En cours d\'investigation',
                                'confirmed' => 'Confirmé',
                                'dismissed' => 'Rejeté',
                                'resolved' => 'Résolu',
                            ])
                            ->default('pending'),
                        Forms\Components\Select::make('priority')
                            ->label('Priorité')
                            ->options([
                                'low' => 'Basse',
                                'medium' => 'Moyenne',
                                'high' => 'Haute',
                                'critical' => 'Critique',
                            ])
                            ->default('medium'),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Notes admin')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('actions_taken')
                            ->label('Actions prises')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fraud_type')
                    ->label('Type')
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'fake_listing' => 'Fausse annonce',
                        'scam' => 'Arnaque',
                        'identity_theft' => 'Usurpation',
                        'payment_fraud' => 'Fraude paiement',
                        'fake_review' => 'Faux avis',
                        'other' => 'Autre',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('Signalé par')
                    ->searchable(),
                Tables\Columns\TextColumn::make('targetUser.name')
                    ->label('Utilisateur')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorité')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'low' => 'Basse',
                        'medium' => 'Moyenne',
                        'high' => 'Haute',
                        'critical' => 'Critique',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'resolved' => 'success',
                        'confirmed' => 'danger',
                        'investigating' => 'info',
                        'pending' => 'warning',
                        'dismissed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'En attente',
                        'investigating' => 'Investigation',
                        'confirmed' => 'Confirmé',
                        'dismissed' => 'Rejeté',
                        'resolved' => 'Résolu',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'investigating' => 'Investigation',
                        'confirmed' => 'Confirmé',
                        'dismissed' => 'Rejeté',
                        'resolved' => 'Résolu',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorité')
                    ->options([
                        'low' => 'Basse',
                        'medium' => 'Moyenne',
                        'high' => 'Haute',
                        'critical' => 'Critique',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),

                // S'assigner le signalement
                Tables\Actions\Action::make('assign')
                    ->label('M\'assigner')
                    ->icon('heroicon-o-user-plus')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Prendre en charge ce signalement ?')
                    ->visible(fn (FraudReport $record): bool => $record->status === 'pending')
                    ->action(function (FraudReport $record): void {
                        $record->assignTo(Auth::id());
                    }),

                // Confirmer la fraude (avec choix d'actions)
                Tables\Actions\Action::make('confirm_fraud')
                    ->label('Confirmer fraude')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->visible(fn (FraudReport $record): bool => in_array($record->status, ['pending', 'investigating']))
                    ->form([
                        Forms\Components\CheckboxList::make('actions')
                            ->label('Actions à appliquer')
                            ->options([
                                'warn_user' => '⚠️ Avertir l\'utilisateur',
                                'suspend_user' => '🔒 Suspendre 30 jours',
                                'ban_user' => '🚫 Bannir définitivement',
                                'remove_listing' => '🏠 Retirer l\'annonce',
                                'remove_review' => '📝 Supprimer l\'avis',
                            ])
                            ->required()
                            ->columns(1),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(2000),
                    ])
                    ->action(function (FraudReport $record, array $data): void {
                        $record->confirm(Auth::id(), $data['actions'], $data['notes'] ?? null);
                        static::applyFraudActions($record, $data['actions']);
                    }),

                // Rejeter le signalement
                Tables\Actions\Action::make('dismiss')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (FraudReport $record): bool => in_array($record->status, ['pending', 'investigating']))
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Motif du rejet')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (FraudReport $record, array $data): void {
                        $record->dismiss(Auth::id(), $data['notes']);
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
            'index' => Pages\ListFraudReports::route('/'),
            'create' => Pages\CreateFraudReport::route('/create'),
            'edit' => Pages\EditFraudReport::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /**
     * Appliquer les actions de fraude confirmée
     * Logique migrée depuis VerificationAdminController::applyFraudActions()
     */
    protected static function applyFraudActions(FraudReport $report, array $actions): void
    {
        foreach ($actions as $action) {
            match ($action) {
                'warn_user' => self::warnUser($report),
                'suspend_user' => self::suspendUser($report),
                'ban_user' => self::banUser($report),
                'remove_listing' => self::removeListing($report),
                'remove_review' => self::removeReview($report),
                default => null,
            };
        }
    }

    private static function warnUser(FraudReport $report): void
    {
        if ($report->targetUser) {
            Notification::send(
                $report->targetUser,
                'system',
                'Avertissement ⚠️',
                'Votre compte a fait l\'objet d\'un signalement pour '.$report->getFraudTypeLabel().'. Veuillez respecter les conditions d\'utilisation de ReziApp.',
                route('pages.cgu'),
                ['fraud_type' => $report->getFraudTypeLabel()],
            );
        }
    }

    private static function suspendUser(FraudReport $report): void
    {
        $report->targetUser?->update([
            'is_suspended' => true,
            'suspended_until' => now()->addDays(30),
            'suspension_reason' => 'Fraude confirmée: '.$report->getFraudTypeLabel(),
        ]);
    }

    private static function banUser(FraudReport $report): void
    {
        if ($report->targetUser) {
            app(VerificationService::class)->blacklistUser(
                $report->targetUser,
                'fraud',
                'Fraude confirmée: '.$report->getFraudTypeLabel(),
                Auth::id(),
            );
        }
    }

    private static function removeListing(FraudReport $report): void
    {
        if ($report->target_type === 'residence') {
            Residence::where('id', $report->target_id)
                ->update(['status' => 'removed']);
        }
    }

    private static function removeReview(FraudReport $report): void
    {
        if ($report->target_type === 'review') {
            Review::where('id', $report->target_id)->delete();
        }
    }
}
