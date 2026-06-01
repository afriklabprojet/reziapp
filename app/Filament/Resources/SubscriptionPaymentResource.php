<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPaymentResource\Pages;
use App\Models\SubscriptionPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionPaymentResource extends Resource
{
    protected static ?string $model = SubscriptionPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Abonnements';

    protected static ?string $navigationLabel = 'Paiements';

    protected static ?string $modelLabel = 'Paiement';

    protected static ?string $pluralModelLabel = 'Paiements d\'abonnement';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['subscription.user', 'user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Paiement')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('subscription_id')
                            ->label('Abonnement')
                            ->relationship('subscription', 'id')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending'   => 'En attente',
                                'completed' => 'Complété',
                                'failed'    => 'Échoué',
                                'refunded'  => 'Remboursé',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('currency')
                            ->label('Devise')
                            ->default('XOF')
                            ->maxLength(3),
                        Forms\Components\TextInput::make('payment_provider')
                            ->label('Prestataire')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('ID Transaction')
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Payé le'),
                        Forms\Components\DateTimePicker::make('period_start')
                            ->label('Début période'),
                        Forms\Components\DateTimePicker::make('period_end')
                            ->label('Fin période'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->badge()
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->badge()
                    ->label('Utilisateur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->badge()
                    ->label('Montant')
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 0, ',', ' ').' '.($record->currency ?? 'FCFA'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger'  => 'failed',
                        'primary' => 'refunded',
                    ])
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'pending'   => 'En attente',
                        'completed' => 'Complété',
                        'failed'    => 'Échoué',
                        'refunded'  => 'Remboursé',
                        default     => $s,
                    }),
                Tables\Columns\TextColumn::make('payment_provider')
                    ->badge()
                    ->label('Prestataire'),
                Tables\Columns\TextColumn::make('paid_at')
                    ->badge()
                    ->label('Payé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_start')
                    ->badge()
                    ->label('Début période')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('period_end')
                    ->badge()
                    ->label('Fin période')
                    ->date('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending'   => 'En attente',
                        'completed' => 'Complété',
                        'failed'    => 'Échoué',
                        'refunded'  => 'Remboursé',
                    ]),
                Tables\Filters\SelectFilter::make('payment_provider')
                    ->label('Prestataire')
                    ->options(fn () => SubscriptionPayment::distinct()->pluck('payment_provider', 'payment_provider')->filter()->toArray()),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSubscriptionPayments::route('/'),
            'create' => Pages\CreateSubscriptionPayment::route('/create'),
            'edit'   => Pages\EditSubscriptionPayment::route('/{record}/edit'),
        ];
    }
}
