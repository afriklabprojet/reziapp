<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantReviewResource\Pages;
use App\Models\TenantReview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantReviewResource extends Resource
{
    protected static ?string $model = TenantReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Avis';

    protected static ?string $navigationLabel = 'Avis sur locataires';

    protected static ?string $modelLabel = 'Avis locataire';

    protected static ?string $pluralModelLabel = 'Avis locataires';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['owner', 'tenant']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Avis')
                    ->schema([
                        Forms\Components\Select::make('owner_id')
                            ->label('Propriétaire')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('tenant_id')
                            ->label('Locataire')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('booking_id')
                            ->label('Réservation')
                            ->relationship('booking', 'reference')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('is_public')
                            ->label('Publié')
                            ->default(false),
                        Forms\Components\Toggle::make('would_rent_again')
                            ->label('Relouerait à ce locataire')
                            ->default(false),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publié le'),
                    ])->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\TextInput::make('cleanliness_rating')
                            ->label('Propreté (1-5)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                        Forms\Components\TextInput::make('respect_rules_rating')
                            ->label('Respect des règles (1-5)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                        Forms\Components\TextInput::make('communication_rating')
                            ->label('Communication (1-5)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                        Forms\Components\TextInput::make('payment_rating')
                            ->label('Paiement (1-5)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                        Forms\Components\TextInput::make('overall_rating')
                            ->label('Note globale (1-5)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                        Forms\Components\Textarea::make('comment')
                            ->label('Commentaire')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Locataire')
                    ->searchable(),
                Tables\Columns\TextColumn::make('overall_rating')
                    ->label('Note')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? "⭐ {$state}/5" : '-'),
                Tables\Columns\IconColumn::make('is_public')
                    ->label('Publié')
                    ->boolean(),
                Tables\Columns\IconColumn::make('would_rent_again')
                    ->label('Re-louerait')
                    ->boolean(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publié le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Publié'),
                Tables\Filters\TernaryFilter::make('would_rent_again')
                    ->label('Re-louerait'),
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
            'index'  => Pages\ListTenantReviews::route('/'),
            'create' => Pages\CreateTenantReview::route('/create'),
            'edit'   => Pages\EditTenantReview::route('/{record}/edit'),
        ];
    }
}
