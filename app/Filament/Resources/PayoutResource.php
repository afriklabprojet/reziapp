<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayoutResource\Pages;
use App\Models\Payout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Gestion des hôtes';

    protected static ?string $navigationLabel = 'Versements';

    protected static ?string $modelLabel = 'Versement';

    protected static ?string $pluralModelLabel = 'Versements';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du versement')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Propriétaire')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('gross_amount')
                            ->label('Montant brut (FCFA)')
                            ->numeric()
                            ->prefix('FCFA')
                            ->required(),
                        Forms\Components\TextInput::make('platform_fee')
                            ->label('Commission plateforme')
                            ->numeric()
                            ->prefix('FCFA')
                            ->default(0),
                        Forms\Components\TextInput::make('net_amount')
                            ->label('Montant net')
                            ->numeric()
                            ->prefix('FCFA')
                            ->required(),
                        Forms\Components\Select::make('payout_method')
                            ->label('Méthode')
                            ->options([
                                'mobile_money' => 'Mobile Money',
                                'bank_transfer' => 'Virement bancaire',
                                'wave' => 'Wave',
                                'orange_money' => 'Orange Money',
                                'mtn_money' => 'MTN Money',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'processing' => 'En cours',
                                'completed' => 'Effectué',
                                'failed' => 'Échoué',
                                'cancelled' => 'Annulé',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Détails bancaires')
                    ->schema([
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Numéro Mobile Money')
                            ->tel(),
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Nom de la banque'),
                        Forms\Components\TextInput::make('bank_account')
                            ->label('Numéro de compte'),
                        Forms\Components\TextInput::make('reference')
                            ->label('Référence'),
                    ])->columns(2),

                Forms\Components\Section::make('Période')
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Début période'),
                        Forms\Components\DatePicker::make('period_end')
                            ->label('Fin période'),
                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label('Traité le'),
                        Forms\Components\Textarea::make('failure_reason')
                            ->label('Raison échec')
                            ->rows(2),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Propriétaire')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gross_amount')
                    ->label('Brut')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Net')
                    ->money('XOF')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('payout_method')
                    ->label('Méthode')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'mobile_money' => 'Mobile Money',
                        'bank_transfer' => 'Virement',
                        'wave' => 'Wave',
                        'orange_money' => 'Orange',
                        'mtn_money' => 'MTN',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'completed' => 'success',
                        'processing' => 'info',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'En attente',
                        'processing' => 'En cours',
                        'completed' => 'Effectué',
                        'failed' => 'Échoué',
                        'cancelled' => 'Annulé',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Demandé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'processing' => 'En cours',
                        'completed' => 'Effectué',
                        'failed' => 'Échoué',
                    ]),
                Tables\Filters\SelectFilter::make('payout_method')
                    ->label('Méthode')
                    ->options([
                        'mobile_money' => 'Mobile Money',
                        'bank_transfer' => 'Virement bancaire',
                        'wave' => 'Wave',
                        'orange_money' => 'Orange Money',
                        'mtn_money' => 'MTN Money',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('process')
                    ->label('Traiter')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(fn ($record) => $record->update([
                        'status' => 'processing',
                    ])),
                Tables\Actions\EditAction::make()->label('Modifier'),
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
            'index' => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
            'edit' => Pages\EditPayout::route('/{record}/edit'),
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
