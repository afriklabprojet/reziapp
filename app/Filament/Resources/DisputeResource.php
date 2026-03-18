<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisputeResource\Pages;
use App\Models\Dispute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DisputeResource extends Resource
{
    protected static ?string $model = Dispute::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Support';

    protected static ?string $navigationLabel = 'Litiges';

    protected static ?string $modelLabel = 'Litige';

    protected static ?string $pluralModelLabel = 'Litiges';

    protected static ?int $navigationSort = 2;

    /**
     * Colonnes réelles BDD : reference, booking_id, opened_by, against_user_id,
     * category, priority, title, description, evidence_files, claimed_amount,
     * claim_justification, status, response, response_evidence, responded_at,
     * resolution_type, resolution_details, resolution_amount, resolved_at,
     * assigned_to, assigned_at, response_deadline, resolution_deadline
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du litige')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create'),
                        Forms\Components\Select::make('booking_id')
                            ->label('Réservation')
                            ->relationship('booking', 'reference')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('opened_by')
                            ->label('Ouvert par')
                            ->relationship('opener', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('against_user_id')
                            ->label('Contre')
                            ->relationship('againstUser', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'cancellation' => 'Annulation',
                                'property_issue' => 'Problème logement',
                                'payment' => 'Paiement',
                                'host_behavior' => 'Comportement hôte',
                                'guest_behavior' => 'Comportement voyageur',
                                'refund' => 'Remboursement',
                                'other' => 'Autre',
                            ])
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->label('Priorité')
                            ->options([
                                'low' => 'Faible',
                                'medium' => 'Moyenne',
                                'high' => 'Haute',
                                'urgent' => 'Urgente',
                            ])
                            ->default('medium')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'open' => 'Ouvert',
                                'under_review' => 'En examen',
                                'awaiting_response' => 'En attente de réponse',
                                'escalated' => 'Escaladé',
                                'resolved' => 'Résolu',
                                'closed' => 'Fermé',
                            ])
                            ->default('open')
                            ->required(),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigné à')
                            ->relationship('assignedAdmin', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('claimed_amount')
                            ->label('Montant réclamé')
                            ->numeric()
                            ->prefix('FCFA'),
                        Forms\Components\Textarea::make('claim_justification')
                            ->label('Justification de la réclamation')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Résolution')
                    ->schema([
                        Forms\Components\Textarea::make('response')
                            ->label('Réponse')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('resolution_type')
                            ->label('Type de résolution')
                            ->options([
                                'refund_full' => 'Remboursement total',
                                'refund_partial' => 'Remboursement partiel',
                                'no_refund' => 'Pas de remboursement',
                                'credit' => 'Crédit plateforme',
                                'mediation' => 'Médiation',
                                'dismissed' => 'Rejeté',
                            ]),
                        Forms\Components\TextInput::make('resolution_amount')
                            ->label('Montant résolu')
                            ->numeric()
                            ->prefix('FCFA'),
                        Forms\Components\Textarea::make('resolution_details')
                            ->label('Détails de résolution')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('response_deadline')
                            ->label('Date limite de réponse'),
                        Forms\Components\DateTimePicker::make('resolution_deadline')
                            ->label('Date limite de résolution'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('opener.name')
                    ->label('Ouvert par')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking.reference')
                    ->label('Résa'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'cancellation' => 'Annulation',
                        'property_issue' => 'Problème logement',
                        'payment' => 'Paiement',
                        'host_behavior' => 'Comp. hôte',
                        'guest_behavior' => 'Comp. voyageur',
                        'refund' => 'Remboursement',
                        'other' => 'Autre',
                        default => $state ?? '-',
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorité')
                    ->badge()
                    ->color(fn (?string $state): string => match($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'low' => 'Faible',
                        'medium' => 'Moyenne',
                        'high' => 'Haute',
                        'urgent' => 'Urgente',
                        default => $state ?? '-',
                    }),
                Tables\Columns\TextColumn::make('claimed_amount')
                    ->label('Réclamé')
                    ->money('XOF'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (?string $state): string => match($state) {
                        'open' => 'danger',
                        'under_review' => 'warning',
                        'awaiting_response' => 'info',
                        'escalated' => 'danger',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'open' => 'Ouvert',
                        'under_review' => 'En examen',
                        'awaiting_response' => 'Attente réponse',
                        'escalated' => 'Escaladé',
                        'resolved' => 'Résolu',
                        'closed' => 'Fermé',
                        default => $state ?? '-',
                    }),
                Tables\Columns\TextColumn::make('assignedAdmin.name')
                    ->label('Assigné à')
                    ->default('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'open' => 'Ouvert',
                        'under_review' => 'En examen',
                        'awaiting_response' => 'Attente réponse',
                        'escalated' => 'Escaladé',
                        'resolved' => 'Résolu',
                        'closed' => 'Fermé',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'cancellation' => 'Annulation',
                        'property_issue' => 'Problème logement',
                        'payment' => 'Paiement',
                        'host_behavior' => 'Comp. hôte',
                        'guest_behavior' => 'Comp. voyageur',
                        'refund' => 'Remboursement',
                        'other' => 'Autre',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorité')
                    ->options([
                        'low' => 'Faible',
                        'medium' => 'Moyenne',
                        'high' => 'Haute',
                        'urgent' => 'Urgente',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\Action::make('assign')
                    ->label('Assigner')
                    ->icon('heroicon-o-user-plus')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigner à')
                            ->relationship('assignedAdmin', 'name', fn ($query) => $query->where('role', 'admin'))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'assigned_to' => $data['assigned_to'],
                        'assigned_at' => now(),
                        'status' => $record->status === 'open' ? 'under_review' : $record->status,
                    ])),
                Tables\Actions\Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => ! in_array($record->status, ['resolved', 'closed']))
                    ->form([
                        Forms\Components\Select::make('resolution_type')
                            ->label('Type de résolution')
                            ->options([
                                'refund_full' => 'Remboursement total',
                                'refund_partial' => 'Remboursement partiel',
                                'no_refund' => 'Pas de remboursement',
                                'credit' => 'Crédit plateforme',
                                'mediation' => 'Médiation',
                                'dismissed' => 'Rejeté',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('resolution_amount')
                            ->label('Montant résolu (FCFA)')
                            ->numeric(),
                        Forms\Components\Textarea::make('resolution_details')
                            ->label('Détails')
                            ->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'status' => 'resolved',
                        'resolution_type' => $data['resolution_type'],
                        'resolution_amount' => $data['resolution_amount'] ?? null,
                        'resolution_details' => $data['resolution_details'],
                        'resolved_at' => now(),
                    ])),
                Tables\Actions\Action::make('escalate')
                    ->label('Escalader')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('danger')
                    ->visible(fn ($record) => ! in_array($record->status, ['escalated', 'resolved', 'closed']))
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'status' => 'escalated',
                        'escalated_at' => now(),
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['opener', 'againstUser', 'booking', 'assignedAdmin']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisputes::route('/'),
            'create' => Pages\CreateDispute::route('/create'),
            'edit' => Pages\EditDispute::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['open', 'under_review', 'awaiting_response', 'escalated'])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
