<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Modération';

    protected static ?string $navigationLabel = 'Avis';

    protected static ?string $modelLabel = 'Avis';

    protected static ?string $pluralModelLabel = 'Avis';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de l\'avis')
                    ->schema([
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Auteur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('booking_id')
                            ->label('Réservation')
                            ->relationship('booking', 'id')
                            ->searchable()
                            ->preload(),
                    ])->columns(3),

                Forms\Components\Section::make('Évaluation')
                    ->schema([
                        Forms\Components\TextInput::make('rating')
                            ->label('Note globale')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->required(),
                        Forms\Components\TextInput::make('cleanliness_rating')
                            ->label('Propreté')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                        Forms\Components\TextInput::make('communication_rating')
                            ->label('Communication')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                        Forms\Components\TextInput::make('location_rating')
                            ->label('Emplacement')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                        Forms\Components\TextInput::make('value_rating')
                            ->label('Rapport qualité/prix')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                        Forms\Components\TextInput::make('accuracy_rating')
                            ->label('Exactitude')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                        Forms\Components\TextInput::make('checkin_rating')
                            ->label('Arrivée')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                    ])->columns(4),

                Forms\Components\Section::make('Commentaire')
                    ->schema([
                        Forms\Components\Textarea::make('comment')
                            ->label('Commentaire')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('pros')
                            ->label('Points positifs')
                            ->rows(2),
                        Forms\Components\Textarea::make('cons')
                            ->label('Points négatifs')
                            ->rows(2),
                        Forms\Components\Toggle::make('would_recommend')
                            ->label('Recommanderait')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Réponse du propriétaire')
                    ->schema([
                        Forms\Components\Textarea::make('owner_response')
                            ->label('Réponse')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('owner_response_at')
                            ->label('Date de réponse'),
                    ]),

                Forms\Components\Section::make('Modération')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvé',
                                'rejected' => 'Rejeté',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Vérifié'),
                        Forms\Components\Textarea::make('moderation_notes')
                            ->label('Notes de modération')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Séjour')
                    ->schema([
                        Forms\Components\DatePicker::make('stay_date_start')
                            ->label('Début du séjour'),
                        Forms\Components\DatePicker::make('stay_date_end')
                            ->label('Fin du séjour'),
                        Forms\Components\TextInput::make('helpful_count')
                            ->label('Votes utiles')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('residence.name')
                    ->label('Résidence')
                    ->searchable()
                    ->sortable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Auteur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Note')
                    ->badge()
                    ->color(fn (int $state): string => match(true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (int $state): string => $state.'/5 ⭐'),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Commentaire')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->comment),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'approved' => 'Approuvé',
                        'pending' => 'En attente',
                        'rejected' => 'Rejeté',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Vérifié')
                    ->boolean(),
                Tables\Columns\IconColumn::make('would_recommend')
                    ->label('Recommande')
                    ->boolean(),
                Tables\Columns\TextColumn::make('helpful_count')
                    ->label('Utile')
                    ->sortable(),
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
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                    ]),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Vérifié'),
                Tables\Filters\TernaryFilter::make('would_recommend')
                    ->label('Recommanderait'),
                Tables\Filters\SelectFilter::make('rating')
                    ->label('Note')
                    ->options([
                        5 => '5 étoiles',
                        4 => '4 étoiles',
                        3 => '3 étoiles',
                        2 => '2 étoiles',
                        1 => '1 étoile',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'approved')
                    ->action(fn ($record) => $record->update([
                        'status' => 'approved',
                    ])),
                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'rejected')
                    ->requiresConfirmation()
                    ->modalHeading('Rejeter cet avis')
                    ->modalDescription('Êtes-vous sûr de vouloir rejeter cet avis ?')
                    ->action(fn ($record) => $record->update([
                        'status' => 'rejected',
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['residence', 'user']);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
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
