<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CancellationResource\Pages;
use App\Models\Cancellation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CancellationResource extends Resource
{
    protected static ?string $model = Cancellation::class;

    protected static ?string $navigationIcon = 'heroicon-o-x-circle';

    protected static ?string $navigationGroup = 'Réservations';

    protected static ?string $navigationLabel = 'Annulations';

    protected static ?string $modelLabel = 'Annulation';

    protected static ?string $pluralModelLabel = 'Annulations';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\Select::make('booking_id')
                            ->label('Réservation')
                            ->relationship('booking', 'id')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('initiated_by')
                            ->label('Annulé par')
                            ->options([
                                'user' => 'Voyageur',
                                'owner' => 'Propriétaire',
                                'admin' => 'Administration',
                                'system' => 'Système',
                            ])
                            ->required(),
                        Forms\Components\Select::make('initiated_by_user_id')
                            ->label('Utilisateur')
                            ->relationship('cancelledByUser', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('reason_category')
                            ->label('Raison')
                            ->options([
                                'change_of_plans' => 'Changement de plans',
                                'found_alternative' => 'Trouvé une alternative',
                                'emergency' => 'Urgence',
                                'property_issue' => 'Problème avec le logement',
                                'host_issue' => 'Problème avec l\'hôte',
                                'double_booking' => 'Double réservation',
                                'property_unavailable' => 'Logement indisponible',
                                'guest_issue' => 'Problème avec le voyageur',
                                'maintenance' => 'Maintenance',
                                'force_majeure' => 'Force majeure',
                                'policy_violation' => 'Violation des conditions',
                                'fraud_suspected' => 'Fraude suspectée',
                                'other' => 'Autre',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('reason_details')
                            ->label('Détails de la raison')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvée',
                                'processed' => 'Traitée',
                                'rejected' => 'Rejetée',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Remboursement')
                    ->schema([
                        Forms\Components\TextInput::make('original_amount')
                            ->label('Montant original')
                            ->numeric()
                            ->prefix('FCFA'),
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Montant remboursé')
                            ->numeric()
                            ->prefix('FCFA'),
                        Forms\Components\TextInput::make('penalty_amount')
                            ->label('Pénalité')
                            ->numeric()
                            ->prefix('FCFA'),
                        Forms\Components\TextInput::make('refund_percent_applied')
                            ->label('% remboursement appliqué')
                            ->numeric()
                            ->suffix('%'),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes admin')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_id')
                    ->label('Résa #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('initiated_by')
                    ->label('Annulé par')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'user' => 'Voyageur',
                        'owner' => 'Propriétaire',
                        'admin' => 'Administration',
                        'system' => 'Système',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'user' => 'info',
                        'owner' => 'warning',
                        'admin' => 'danger',
                        'system' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('cancelledByUser.name')
                    ->label('Utilisateur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reason_category')
                    ->label('Raison')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'change_of_plans' => 'Changement',
                        'found_alternative' => 'Alternative trouvée',
                        'emergency' => 'Urgence',
                        'property_issue' => 'Problème logement',
                        'host_issue' => 'Problème hôte',
                        'double_booking' => 'Double réservation',
                        'property_unavailable' => 'Indisponible',
                        'guest_issue' => 'Problème voyageur',
                        'maintenance' => 'Maintenance',
                        'force_majeure' => 'Force majeure',
                        'policy_violation' => 'Violation',
                        'fraud_suspected' => 'Fraude',
                        'other' => 'Autre',
                        default => $state ?? '',
                    }),
                Tables\Columns\TextColumn::make('refund_amount')
                    ->label('Remboursé')
                    ->money('XOF'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'approved' => 'success',
                        'processed' => 'info',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'En attente',
                        'approved' => 'Approuvée',
                        'processed' => 'Traitée',
                        'rejected' => 'Rejetée',
                        default => $state,
                    }),
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
                        'pending' => 'En attente',
                        'approved' => 'Approuvée',
                        'processed' => 'Traitée',
                        'rejected' => 'Rejetée',
                    ]),
                Tables\Filters\SelectFilter::make('initiated_by')
                    ->label('Annulé par')
                    ->options([
                        'user' => 'Voyageur',
                        'owner' => 'Propriétaire',
                        'admin' => 'Administration',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCancellations::route('/'),
            'create' => Pages\CreateCancellation::route('/create'),
            'edit' => Pages\EditCancellation::route('/{record}/edit'),
        ];
    }
}
