<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralResource\Pages;
use App\Models\Referral;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Parrainages';

    protected static ?string $modelLabel = 'Parrainage';

    protected static ?string $pluralModelLabel = 'Parrainages';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Participants')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\Select::make('referrer_id')
                            ->label('Parrain')
                            ->relationship('referrer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('L\'utilisateur qui a parrainé'),
                        Forms\Components\Select::make('referred_id')
                            ->label('Filleul')
                            ->relationship('referred', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('L\'utilisateur qui a été parrainé'),
                    ])->columns(2),

                Forms\Components\Section::make('Statut')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente - Le filleul s\'est inscrit',
                                'qualified' => 'Qualifié - Le filleul a effectué une réservation',
                                'rewarded' => 'Récompensé - Les récompenses ont été versées',
                                'cancelled' => 'Annulé - Délai dépassé ou annulé',
                            ])
                            ->default('pending')
                            ->required()
                            ->live(),
                        Forms\Components\DateTimePicker::make('qualified_at')
                            ->label('Date de qualification')
                            ->visible(fn ($get) => in_array($get('status'), ['qualified', 'rewarded'])),
                        Forms\Components\DateTimePicker::make('rewarded_at')
                            ->label('Date de récompense')
                            ->visible(fn ($get) => $get('status') === 'rewarded'),
                    ])->columns(3),

                Forms\Components\Section::make('Récompenses')
                    ->icon('heroicon-o-gift')
                    ->schema([
                        Forms\Components\Select::make('reward_type')
                            ->label('Type de récompense')
                            ->options([
                                'credit' => 'Crédit sur le compte',
                                'coupon' => 'Code promo',
                                'cash' => 'Paiement direct',
                            ])
                            ->default('credit'),
                        Forms\Components\TextInput::make('referrer_reward')
                            ->label('Récompense parrain')
                            ->numeric()
                            ->prefix('FCFA')
                            ->helperText('Montant accordé au parrain'),
                        Forms\Components\TextInput::make('referred_reward')
                            ->label('Récompense filleul')
                            ->numeric()
                            ->prefix('FCFA')
                            ->helperText('Montant accordé au filleul'),
                    ])->columns(3),

                Forms\Components\Section::make('Notes')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes internes')
                            ->rows(3)
                            ->placeholder('Notes administratives sur ce parrainage...'),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Parrain')
                    ->description(fn ($record) => $record->referrer?->email)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('referred.name')
                    ->label('Filleul')
                    ->description(fn ($record) => $record->referred?->email)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('referrer.referral_code')
                    ->label('Code')
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->copyMessage('Code copié!'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'pending' => 'warning',
                        'qualified' => 'info',
                        'rewarded' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'En attente',
                        'qualified' => 'Qualifié',
                        'rewarded' => 'Récompensé',
                        'cancelled' => 'Annulé',
                        default => $state,
                    })
                    ->icon(fn (string $state): string => match($state) {
                        'pending' => 'heroicon-o-clock',
                        'qualified' => 'heroicon-o-check',
                        'rewarded' => 'heroicon-o-gift',
                        'cancelled' => 'heroicon-o-x-mark',
                        default => 'heroicon-o-question-mark-circle',
                    }),
                Tables\Columns\TextColumn::make('referrer_reward')
                    ->label('Rép. Parrain')
                    ->money('XOF')
                    ->placeholder('—')
                    ->color(fn ($record) => $record->status === 'rewarded' ? 'success' : null),
                Tables\Columns\TextColumn::make('referred_reward')
                    ->label('Rép. Filleul')
                    ->money('XOF')
                    ->placeholder('—')
                    ->color(fn ($record) => $record->status === 'rewarded' ? 'success' : null),
                Tables\Columns\TextColumn::make('reward_type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'credit' => 'Crédit',
                        'coupon' => 'Coupon',
                        'cash' => 'Cash',
                        default => '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'qualified' => 'Qualifié',
                        'rewarded' => 'Récompensé',
                        'cancelled' => 'Annulé',
                    ]),
                Tables\Filters\SelectFilter::make('reward_type')
                    ->label('Type de récompense')
                    ->options([
                        'credit' => 'Crédit',
                        'coupon' => 'Coupon',
                        'cash' => 'Cash',
                    ]),
                Tables\Filters\Filter::make('has_reward')
                    ->label('Avec récompense')
                    ->query(fn (Builder $query) => $query->whereNotNull('referrer_reward')),
                Tables\Filters\Filter::make('this_month')
                    ->label('Ce mois')
                    ->query(fn (Builder $query) => $query->whereMonth('created_at', now()->month)),
            ])
            ->actions([
                Tables\Actions\Action::make('qualify')
                    ->label('Qualifier')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Qualifier ce parrainage')
                    ->modalDescription('Confirmer que le filleul a rempli les conditions (ex: première réservation effectuée)?')
                    ->action(fn ($record) => $record->update([
                        'status' => 'qualified',
                        'qualified_at' => now(),
                    ])),
                Tables\Actions\Action::make('reward')
                    ->label('Récompenser')
                    ->icon('heroicon-o-gift')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'qualified')
                    ->form([
                        Forms\Components\Select::make('reward_type')
                            ->label('Type de récompense')
                            ->options([
                                'credit' => 'Crédit sur le compte',
                                'coupon' => 'Code promo',
                                'cash' => 'Paiement direct',
                            ])
                            ->default('credit')
                            ->required(),
                        Forms\Components\TextInput::make('referrer_reward')
                            ->label('Récompense parrain (FCFA)')
                            ->numeric()
                            ->default(5000)
                            ->required(),
                        Forms\Components\TextInput::make('referred_reward')
                            ->label('Récompense filleul (FCFA)')
                            ->numeric()
                            ->default(2500)
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reward(
                            $data['referrer_reward'],
                            $data['referred_reward'],
                            $data['reward_type'],
                        );
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'qualified']))
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->cancel()),
                Tables\Actions\ViewAction::make()->label(''),
                Tables\Actions\EditAction::make()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('qualify_bulk')
                        ->label('Qualifier les sélectionnés')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update([
                            'status' => 'qualified',
                            'qualified_at' => now(),
                        ])),
                    Tables\Actions\BulkAction::make('cancel_bulk')
                        ->label('Annuler les sélectionnés')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->cancel()),
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Participants')
                    ->schema([
                        Infolists\Components\TextEntry::make('referrer.name')
                            ->label('Parrain'),
                        Infolists\Components\TextEntry::make('referrer.email')
                            ->label('Email parrain'),
                        Infolists\Components\TextEntry::make('referred.name')
                            ->label('Filleul'),
                        Infolists\Components\TextEntry::make('referred.email')
                            ->label('Email filleul'),
                    ])->columns(2),
                Infolists\Components\Section::make('Détails')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'pending' => 'warning',
                                'qualified' => 'info',
                                'rewarded' => 'success',
                                'expired' => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('referrer_reward')
                            ->label('Récompense parrain')
                            ->money('XOF'),
                        Infolists\Components\TextEntry::make('referred_reward')
                            ->label('Récompense filleul')
                            ->money('XOF'),
                        Infolists\Components\TextEntry::make('reward_type')
                            ->label('Type de récompense'),
                    ])->columns(4),
                Infolists\Components\Section::make('Dates')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('qualified_at')
                            ->label('Qualifié le')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('rewarded_at')
                            ->label('Récompensé le')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferrals::route('/'),
            'create' => Pages\CreateReferral::route('/create'),
            'view' => Pages\ViewReferral::route('/{record}'),
            'edit' => Pages\EditReferral::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'qualified')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
