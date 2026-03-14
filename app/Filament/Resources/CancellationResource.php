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
                        Forms\Components\Select::make('cancelled_by')
                            ->label('Annulé par')
                            ->relationship('cancelledBy', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('reason')
                            ->label('Raison')
                            ->options([
                                'change_of_plans' => 'Changement de plans',
                                'found_better' => 'Meilleure option trouvée',
                                'host_issue' => 'Problème avec l\'hôte',
                                'emergency' => 'Urgence',
                                'other' => 'Autre',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvée',
                                'rejected' => 'Rejetée',
                                'refunded' => 'Remboursée',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Remboursement')
                    ->schema([
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Montant remboursé')
                            ->numeric()
                            ->prefix('FCFA'),
                        Forms\Components\TextInput::make('penalty_amount')
                            ->label('Pénalité')
                            ->numeric()
                            ->prefix('FCFA'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
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
                Tables\Columns\TextColumn::make('cancelledBy.name')
                    ->label('Annulé par')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Raison')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'change_of_plans' => 'Changement',
                        'found_better' => 'Meilleure option',
                        'host_issue' => 'Problème hôte',
                        'emergency' => 'Urgence',
                        'other' => 'Autre',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('refund_amount')
                    ->label('Remboursé')
                    ->money('XOF'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'approved' => 'success',
                        'refunded' => 'info',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'En attente',
                        'approved' => 'Approuvée',
                        'rejected' => 'Rejetée',
                        'refunded' => 'Remboursée',
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
                        'rejected' => 'Rejetée',
                        'refunded' => 'Remboursée',
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
