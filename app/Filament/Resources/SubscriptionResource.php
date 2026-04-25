<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Abonnements';

    protected static ?string $navigationLabel = 'Abonnements';

    protected static ?string $modelLabel = 'Abonnement';

    protected static ?string $pluralModelLabel = 'Abonnements';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'plan']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Abonnement')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Propriétaire')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('subscription_plan_id')
                            ->label('Plan')
                            ->relationship('plan', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'active'   => 'Actif',
                                'trial'    => 'Essai',
                                'past_due' => 'En retard',
                                'cancelled' => 'Annulé',
                                'expired'  => 'Expiré',
                            ])
                            ->required(),
                        Forms\Components\Select::make('billing_cycle')
                            ->label('Cycle de facturation')
                            ->options([
                                'monthly' => 'Mensuel',
                                'yearly'  => 'Annuel',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant (FCFA)')
                            ->numeric(),
                        Forms\Components\Toggle::make('auto_renew')
                            ->label('Renouvellement automatique')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Période')
                    ->schema([
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Fin d\'essai'),
                        Forms\Components\DateTimePicker::make('current_period_start')
                            ->label('Début de période'),
                        Forms\Components\DateTimePicker::make('current_period_end')
                            ->label('Fin de période'),
                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label('Annulé le'),
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Raison d\'annulation')
                            ->rows(2),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->badge()
                    ->label('Propriétaire')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->badge()
                    ->label('Plan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'success' => 'active',
                        'info'    => 'trial',
                        'warning' => 'past_due',
                        'danger'  => ['cancelled', 'expired'],
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active'   => 'Actif',
                        'trial'    => 'Essai',
                        'past_due' => 'En retard',
                        'cancelled' => 'Annulé',
                        'expired'  => 'Expiré',
                        default    => $state,
                    }),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->badge()
                    ->label('Cycle')
                    ->formatStateUsing(fn ($s) => $s === 'monthly' ? 'Mensuel' : 'Annuel'),
                Tables\Columns\TextColumn::make('amount')
                    ->badge()
                    ->label('Montant')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ').' FCFA'),
                Tables\Columns\TextColumn::make('current_period_end')
                    ->badge()
                    ->label('Expire le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('auto_renew')
                    ->label('Auto-renouvellement')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active'   => 'Actif',
                        'trial'    => 'Essai',
                        'past_due' => 'En retard',
                        'cancelled' => 'Annulé',
                        'expired'  => 'Expiré',
                    ]),
                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->label('Cycle')
                    ->options(['monthly' => 'Mensuel', 'yearly' => 'Annuel']),
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
            'index'  => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit'   => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'past_due')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
