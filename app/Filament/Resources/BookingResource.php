<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Réservations';

    protected static ?string $navigationLabel = 'Réservations';

    protected static ?string $modelLabel = 'Réservation';

    protected static ?string $pluralModelLabel = 'Réservations';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['residence', 'user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Détails de la réservation')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Client')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('booking_type')
                            ->label('Type')
                            ->options([
                                'instant' => 'Réservation instantanée',
                                'request' => 'Demande de réservation',
                            ])
                            ->default('instant'),
                    ])->columns(2),

                Forms\Components\Section::make('Dates et voyageurs')
                    ->schema([
                        Forms\Components\DatePicker::make('check_in')
                            ->label('Date d\'arrivée')
                            ->required(),
                        Forms\Components\DatePicker::make('check_out')
                            ->label('Date de départ')
                            ->required()
                            ->after('check_in'),
                        Forms\Components\TextInput::make('nights')
                            ->label('Nuits')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('guests')
                            ->label('Voyageurs')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                        Forms\Components\TextInput::make('adults')
                            ->label('Adultes')
                            ->numeric()
                            ->default(1),
                        Forms\Components\TextInput::make('children')
                            ->label('Enfants')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('Tarification')
                    ->schema([
                        Forms\Components\TextInput::make('price_per_night')
                            ->label('Prix/nuit')
                            ->numeric()
                            ->prefix('FCFA'),
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Sous-total')
                            ->numeric()
                            ->prefix('FCFA'),
                        Forms\Components\TextInput::make('cleaning_fee')
                            ->label('Frais de ménage')
                            ->numeric()
                            ->prefix('FCFA')
                            ->default(0),
                        Forms\Components\TextInput::make('service_fee')
                            ->label('Frais de service')
                            ->numeric()
                            ->prefix('FCFA')
                            ->default(0),
                        Forms\Components\TextInput::make('total_discount')
                            ->label('Réduction')
                            ->numeric()
                            ->prefix('FCFA')
                            ->default(0),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total')
                            ->numeric()
                            ->prefix('FCFA')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut réservation')
                            ->options([
                                'pending' => 'En attente',
                                'confirmed' => 'Confirmée',
                                'cancelled' => 'Annulée',
                                'completed' => 'Terminée',
                                'no_show' => 'Non présenté',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\Select::make('payment_status')
                            ->label('Statut paiement')
                            ->options([
                                'pending' => 'En attente',
                                'partial' => 'Partiel',
                                'paid' => 'Payé',
                                'refunded' => 'Remboursé',
                                'failed' => 'Échoué',
                            ])
                            ->default('pending'),
                        Forms\Components\TextInput::make('payment_method')
                            ->label('Méthode de paiement'),
                    ])->columns(3),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('guest_message')
                            ->label('Message du voyageur')
                            ->rows(2),
                        Forms\Components\Textarea::make('owner_notes')
                            ->label('Notes propriétaire')
                            ->rows(2),
                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Notes internes (admin)')
                            ->rows(2),
                    ])->columns(1),
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
                Tables\Columns\TextColumn::make('residence.name')
                    ->label('Résidence')
                    ->searchable()
                    ->sortable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_in')
                    ->label('Arrivée')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out')
                    ->label('Départ')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nights')
                    ->label('Nuits')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        'no_show' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'En attente',
                        'confirmed' => 'Confirmée',
                        'cancelled' => 'Annulée',
                        'completed' => 'Terminée',
                        'no_show' => 'Non présenté',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Paiement')
                    ->badge()
                    ->color(fn (?string $state): string => match($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'partial' => 'info',
                        'refunded' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'pending' => 'En attente',
                        'partial' => 'Partiel',
                        'paid' => 'Payé',
                        'refunded' => 'Remboursé',
                        'failed' => 'Échoué',
                        default => '-',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'confirmed' => 'Confirmée',
                        'cancelled' => 'Annulée',
                        'completed' => 'Terminée',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Paiement')
                    ->options([
                        'pending' => 'En attente',
                        'paid' => 'Payé',
                        'refunded' => 'Remboursé',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\Action::make('confirm')
                    ->label('Confirmer')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'confirmed',
                            'confirmed_at' => now(),
                        ]);

                        // Notifier le voyageur
                        $record->user?->notify(new \App\Notifications\BookingConfirmed($record));

                        // Auto-qualifier le parrainage si le propriétaire est un filleul
                        $owner = $record->residence?->owner;
                        if ($owner && $owner->referred_by) {
                            $referral = \App\Models\Referral::where('referred_id', $owner->id)
                                ->where('status', 'pending')
                                ->first();

                            if ($referral) {
                                $referral->qualify();

                                if ($referral->referrer) {
                                    $referral->referrer->notify(new \App\Notifications\ReferralQualified($referral));
                                }
                            }
                        }
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'confirmed']))
                    ->requiresConfirmation()
                    ->modalHeading('Annuler la réservation')
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Raison de l\'annulation')
                            ->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancelled_by' => 'admin',
                        'cancellation_reason' => $data['cancellation_reason'],
                    ])),
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
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
