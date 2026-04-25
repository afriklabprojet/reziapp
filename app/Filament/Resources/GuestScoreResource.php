<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuestScoreResource\Pages;
use App\Models\GuestScore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GuestScoreResource extends Resource
{
    protected static ?string $model = GuestScore::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Utilisateurs';

    protected static ?string $navigationLabel = 'Scores voyageurs';

    protected static ?string $modelLabel = 'Score voyageur';

    protected static ?string $pluralModelLabel = 'Scores voyageurs';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Utilisateur')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('risk_level')
                            ->label('Niveau de risque')
                            ->options([
                                'low'    => 'Faible',
                                'medium' => 'Moyen',
                                'high'   => 'Élevé',
                            ]),
                        Forms\Components\DateTimePicker::make('last_calculated_at')
                            ->label('Dernière mise à jour'),
                    ])->columns(2),

                Forms\Components\Section::make('Scores')
                    ->schema([
                        Forms\Components\TextInput::make('total_score')
                            ->label('Score total')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('identity_score')
                            ->label('Score identité')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('booking_score')
                            ->label('Score réservations')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('review_score')
                            ->label('Score avis')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('seniority_score')
                            ->label('Score ancienneté')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('Statistiques')
                    ->schema([
                        Forms\Components\TextInput::make('total_bookings')
                            ->label('Total réservations')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('completed_bookings')
                            ->label('Réservations complétées')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('cancelled_bookings')
                            ->label('Annulations')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('cancellation_rate')
                            ->label('Taux d\'annulation (%)')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('average_owner_rating')
                            ->label('Note moyenne propriétaires')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('damage_reports_count')
                            ->label('Signalements de dégâts')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->badge()
                    ->label('Voyageur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_score')
                    ->badge()
                    ->label('Score')
                    ->sortable(),
                Tables\Columns\TextColumn::make('risk_level')
                    ->badge()
                    ->label('Risque')
                    ->colors([
                        'success' => 'low',
                        'warning' => 'medium',
                        'danger'  => 'high',
                    ])
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'low'    => 'Faible',
                        'medium' => 'Moyen',
                        'high'   => 'Élevé',
                        default  => $s,
                    }),
                Tables\Columns\TextColumn::make('total_bookings')
                    ->badge()
                    ->label('Réservations'),
                Tables\Columns\TextColumn::make('cancellation_rate')
                    ->badge()
                    ->label('Annulations %'),
                Tables\Columns\TextColumn::make('damage_reports_count')
                    ->badge()
                    ->label('Dégâts'),
                Tables\Columns\TextColumn::make('last_calculated_at')
                    ->badge()
                    ->label('Calculé le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('risk_level')
                    ->label('Niveau de risque')
                    ->options([
                        'low'    => 'Faible',
                        'medium' => 'Moyen',
                        'high'   => 'Élevé',
                    ]),
            ])
            ->defaultSort('total_score', 'asc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGuestScores::route('/'),
            'create' => Pages\CreateGuestScore::route('/create'),
            'edit'   => Pages\EditGuestScore::route('/{record}/edit'),
        ];
    }
}
