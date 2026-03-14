<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisputeResource\Pages;
use App\Models\Dispute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DisputeResource extends Resource
{
    protected static ?string $model = Dispute::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Support';

    protected static ?string $navigationLabel = 'Litiges';

    protected static ?string $modelLabel = 'Litige';

    protected static ?string $pluralModelLabel = 'Litiges';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du litige')
                    ->schema([
                        Forms\Components\Select::make('booking_id')
                            ->label('Réservation')
                            ->relationship('booking', 'id')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Demandeur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'refund' => 'Remboursement',
                                'damage' => 'Dommages',
                                'service' => 'Problème de service',
                                'safety' => 'Sécurité',
                                'other' => 'Autre',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'open' => 'Ouvert',
                                'investigating' => 'En investigation',
                                'resolved_for_guest' => 'Résolu (voyageur)',
                                'resolved_for_host' => 'Résolu (hôte)',
                                'closed' => 'Fermé',
                            ])
                            ->default('open')
                            ->required(),
                        Forms\Components\TextInput::make('amount_claimed')
                            ->label('Montant réclamé')
                            ->numeric()
                            ->prefix('FCFA'),
                        Forms\Components\TextInput::make('amount_awarded')
                            ->label('Montant accordé')
                            ->numeric()
                            ->prefix('FCFA'),
                    ])->columns(2),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Notes de résolution')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Demandeur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('booking_id')
                    ->label('Résa #'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'refund' => 'Remboursement',
                        'damage' => 'Dommages',
                        'service' => 'Service',
                        'safety' => 'Sécurité',
                        'other' => 'Autre',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('amount_claimed')
                    ->label('Réclamé')
                    ->money('XOF'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'open' => 'danger',
                        'investigating' => 'warning',
                        'resolved_for_guest' => 'success',
                        'resolved_for_host' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'open' => 'Ouvert',
                        'investigating' => 'Investigation',
                        'resolved_for_guest' => 'Résolu (voyageur)',
                        'resolved_for_host' => 'Résolu (hôte)',
                        'closed' => 'Fermé',
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
                        'open' => 'Ouvert',
                        'investigating' => 'Investigation',
                        'resolved_for_guest' => 'Résolu (voyageur)',
                        'resolved_for_host' => 'Résolu (hôte)',
                        'closed' => 'Fermé',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'refund' => 'Remboursement',
                        'damage' => 'Dommages',
                        'service' => 'Service',
                        'safety' => 'Sécurité',
                        'other' => 'Autre',
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
            'index' => Pages\ListDisputes::route('/'),
            'create' => Pages\CreateDispute::route('/create'),
            'edit' => Pages\EditDispute::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['open', 'investigating'])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
