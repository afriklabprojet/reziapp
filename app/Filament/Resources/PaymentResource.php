<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?string $navigationLabel = 'Paiements';

    protected static ?string $modelLabel = 'Paiement';

    protected static ?string $pluralModelLabel = 'Paiements';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'booking', 'paymentMethod']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Payment')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informations')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('reference')
                                    ->label('Référence')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('uuid')
                                    ->label('UUID')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\Select::make('user_id')
                                    ->label('Utilisateur')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('booking_id')
                                    ->label('Réservation')
                                    ->relationship('booking', 'reference')
                                    ->searchable()
                                    ->preload(),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Montants')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Montant')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(
                                        fn ($state, $set, $get) =>
                                        $set('total_amount', floatval($state) + floatval($get('fee') ?? 0)),
                                    ),
                                Forms\Components\TextInput::make('fee')
                                    ->label('Frais de service')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(
                                        fn ($state, $set, $get) =>
                                        $set('total_amount', floatval($get('amount') ?? 0) + floatval($state)),
                                    ),
                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\Select::make('currency')
                                    ->label('Devise')
                                    ->options([
                                        'XOF' => 'FCFA (XOF)',
                                        'EUR' => 'Euro (EUR)',
                                        'USD' => 'Dollar (USD)',
                                    ])
                                    ->default('XOF'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Méthode')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Type de paiement')
                                    ->options([
                                        'booking' => 'Réservation',
                                        'deposit' => 'Dépôt/Caution',
                                        'extension' => 'Extension de séjour',
                                        'penalty' => 'Pénalité',
                                        'refund' => 'Remboursement',
                                        'payout' => 'Versement propriétaire',
                                    ])
                                    ->default('booking')
                                    ->required(),
                                Forms\Components\Select::make('payment_method_id')
                                    ->label('Méthode de paiement')
                                    ->relationship('paymentMethod', 'label')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('phone_number')
                                    ->label('Numéro de téléphone')
                                    ->tel()
                                    ->placeholder('+225 XX XX XX XX XX'),
                                Forms\Components\TextInput::make('provider_reference')
                                    ->label('Référence fournisseur')
                                    ->placeholder('ID de transaction du fournisseur'),
                                Forms\Components\TextInput::make('provider_transaction_id')
                                    ->label('ID Transaction')
                                    ->placeholder('ID unique de la transaction'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Statut')
                            ->icon('heroicon-o-flag')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'pending' => 'En attente',
                                        'processing' => 'En cours de traitement',
                                        'completed' => 'Complété',
                                        'failed' => 'Échoué',
                                        'cancelled' => 'Annulé',
                                        'refunded' => 'Remboursé',
                                        'partial_refund' => 'Remboursement partiel',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->live(),
                                Forms\Components\DateTimePicker::make('initiated_at')
                                    ->label('Initié le'),
                                Forms\Components\DateTimePicker::make('confirmed_at')
                                    ->label('Confirmé le'),
                                Forms\Components\DateTimePicker::make('completed_at')
                                    ->label('Complété le'),
                                Forms\Components\DateTimePicker::make('failed_at')
                                    ->label('Échoué le')
                                    ->visible(fn ($get) => in_array($get('status'), ['failed', 'cancelled'])),
                                Forms\Components\Textarea::make('failure_reason')
                                    ->label('Raison de l\'échec')
                                    ->rows(2)
                                    ->visible(fn ($get) => in_array($get('status'), ['failed', 'cancelled']))
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Référence copiée!')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client')
                    ->description(fn ($record) => $record->user?->email)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking.reference')
                    ->label('Réservation')
                    ->url(fn ($record) => $record->booking_id
                        ? route('filament.admin.resources.bookings.edit', $record->booking_id)
                        : null)
                    ->color('primary')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('XOF')
                            ->label('Total'),
                    ]),
                Tables\Columns\TextColumn::make('fee')
                    ->label('Frais')
                    ->money('XOF')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?string $state): string => match($state) {
                        'booking' => 'primary',
                        'deposit' => 'info',
                        'extension' => 'warning',
                        'penalty' => 'danger',
                        'refund' => 'gray',
                        'payout' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'booking' => 'Réservation',
                        'deposit' => 'Caution',
                        'extension' => 'Extension',
                        'penalty' => 'Pénalité',
                        'refund' => 'Remboursement',
                        'payout' => 'Versement',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'processing' => 'info',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        'refunded' => 'purple',
                        'partial_refund' => 'purple',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match($state) {
                        'completed' => 'heroicon-o-check-circle',
                        'pending' => 'heroicon-o-clock',
                        'processing' => 'heroicon-o-arrow-path',
                        'failed' => 'heroicon-o-x-circle',
                        'cancelled' => 'heroicon-o-x-mark',
                        'refunded' => 'heroicon-o-arrow-uturn-left',
                        'partial_refund' => 'heroicon-o-arrow-uturn-left',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'En attente',
                        'processing' => 'En cours',
                        'completed' => 'Complété',
                        'failed' => 'Échoué',
                        'cancelled' => 'Annulé',
                        'refunded' => 'Remboursé',
                        'partial_refund' => 'Remb. partiel',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Téléphone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Complété le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'processing' => 'En cours',
                        'completed' => 'Complété',
                        'failed' => 'Échoué',
                        'refunded' => 'Remboursé',
                        'cancelled' => 'Annulé',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'booking' => 'Réservation',
                        'deposit' => 'Caution',
                        'extension' => 'Extension',
                        'penalty' => 'Pénalité',
                        'refund' => 'Remboursement',
                        'payout' => 'Versement',
                    ]),
                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Montant min')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Montant max')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min_amount'], fn ($q, $v) => $q->where('amount', '>=', $v))
                            ->when($data['max_amount'], fn ($q, $v) => $q->where('amount', '<=', $v));
                    }),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                            ->when($data['until'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
                    }),
                Tables\Filters\Filter::make('pending')
                    ->label('En attente seulement')
                    ->query(fn (Builder $query) => $query->where('status', 'pending'))
                    ->toggle(),
                Tables\Filters\Filter::make('today')
                    ->label('Aujourd\'hui')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today()))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Détails'),
                    Tables\Actions\EditAction::make()->label('Modifier'),
                    Tables\Actions\Action::make('markCompleted')
                        ->label('Marquer complété')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'processing']))
                        ->requiresConfirmation()
                        ->modalHeading('Confirmer le paiement')
                        ->modalDescription('Marquer ce paiement comme complété?')
                        ->action(function ($record) {
                            $record->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                            ]);

                            // Mettre à jour le statut de la réservation si existe
                            if ($record->booking) {
                                $record->booking->update(['payment_status' => 'paid']);
                            }

                            Notification::make()
                                ->title('Paiement marqué comme complété')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('markFailed')
                        ->label('Marquer échoué')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'processing']))
                        ->form([
                            Forms\Components\Textarea::make('failure_reason')
                                ->label('Raison de l\'échec')
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => 'failed',
                                'failed_at' => now(),
                                'failure_reason' => $data['failure_reason'],
                            ]);

                            Notification::make()
                                ->title('Paiement marqué comme échoué')
                                ->warning()
                                ->send();
                        }),
                    Tables\Actions\Action::make('refund')
                        ->label('Rembourser')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('purple')
                        ->visible(fn ($record) => $record->status === 'completed')
                        ->form([
                            Forms\Components\TextInput::make('refund_amount')
                                ->label('Montant à rembourser')
                                ->numeric()
                                ->default(fn ($record) => $record->amount)
                                ->required()
                                ->prefix('FCFA'),
                            Forms\Components\Textarea::make('refund_reason')
                                ->label('Raison du remboursement')
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $isPartial = $data['refund_amount'] < $record->amount;

                            // Créer un paiement de remboursement
                            Payment::create([
                                'user_id' => $record->user_id,
                                'booking_id' => $record->booking_id,
                                'amount' => -$data['refund_amount'],
                                'fee' => 0,
                                'total_amount' => -$data['refund_amount'],
                                'currency' => $record->currency,
                                'type' => 'refund',
                                'status' => 'completed',
                                'reference' => 'REF-'.strtoupper(uniqid()),
                                'completed_at' => now(),
                                'metadata' => [
                                    'original_payment_id' => $record->id,
                                    'refund_reason' => $data['refund_reason'],
                                ],
                            ]);

                            $record->update([
                                'status' => $isPartial ? 'partial_refund' : 'refunded',
                            ]);

                            Notification::make()
                                ->title($isPartial ? 'Remboursement partiel effectué' : 'Remboursement complet effectué')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_completed')
                        ->label('Marquer complétés')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ])),
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informations du paiement')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Référence')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('uuid')
                            ->label('UUID')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Client'),
                        Infolists\Components\TextEntry::make('booking.reference')
                            ->label('Réservation')
                            ->url(fn ($record) => $record->booking_id
                                ? route('filament.admin.resources.bookings.edit', $record->booking_id)
                                : null),
                    ])->columns(4),
                Infolists\Components\Section::make('Montants')
                    ->schema([
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Montant')
                            ->money('XOF'),
                        Infolists\Components\TextEntry::make('fee')
                            ->label('Frais')
                            ->money('XOF'),
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total')
                            ->money('XOF')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('currency')
                            ->label('Devise'),
                    ])->columns(4),
                Infolists\Components\Section::make('Méthode et statut')
                    ->schema([
                        Infolists\Components\TextEntry::make('type')
                            ->label('Type')
                            ->badge(),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'processing' => 'info',
                                'failed' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('phone_number')
                            ->label('Téléphone'),
                        Infolists\Components\TextEntry::make('provider_reference')
                            ->label('Réf. fournisseur'),
                    ])->columns(4),
                Infolists\Components\Section::make('Dates')
                    ->schema([
                        Infolists\Components\TextEntry::make('initiated_at')
                            ->label('Initié')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('confirmed_at')
                            ->label('Confirmé')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('Complété')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Créé')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(4),
                Infolists\Components\Section::make('Erreur')
                    ->schema([
                        Infolists\Components\TextEntry::make('failure_reason')
                            ->label('Raison de l\'échec'),
                    ])
                    ->visible(fn ($record) => !empty($record->failure_reason)),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
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
}
