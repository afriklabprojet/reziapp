<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageSequenceResource\Pages;
use App\Models\MessageSequence;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MessageSequenceResource extends Resource
{
    protected static ?string $model = MessageSequence::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Messagerie';

    protected static ?string $modelLabel = 'Séquence de messages';

    protected static ?string $pluralModelLabel = 'Séquences de messages';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Séquence')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Propriétaire')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('trigger_event')
                            ->label('Déclencheur')
                            ->options([
                                MessageSequence::TRIGGER_BOOKING_CONFIRMED    => 'Réservation confirmée',
                                MessageSequence::TRIGGER_CHECK_IN_APPROACHING => 'Check-in approchant',
                                MessageSequence::TRIGGER_POST_CHECKOUT        => 'Après le départ',
                                MessageSequence::TRIGGER_PRE_CHECKOUT         => 'Avant le départ',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Propriétaire')
                    ->searchable(),
                Tables\Columns\TextColumn::make('residence.title')
                    ->label('Résidence')
                    ->placeholder('Toutes')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('trigger_event')
                    ->label('Déclencheur')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        MessageSequence::TRIGGER_BOOKING_CONFIRMED    => 'Réservation confirmée',
                        MessageSequence::TRIGGER_CHECK_IN_APPROACHING => 'Check-in approchant',
                        MessageSequence::TRIGGER_POST_CHECKOUT        => 'Après le départ',
                        MessageSequence::TRIGGER_PRE_CHECKOUT         => 'Avant le départ',
                        default                                       => $state,
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('steps_count')
                    ->label('Étapes')
                    ->counts('steps'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trigger_event')
                    ->label('Déclencheur')
                    ->options([
                        MessageSequence::TRIGGER_BOOKING_CONFIRMED    => 'Réservation confirmée',
                        MessageSequence::TRIGGER_CHECK_IN_APPROACHING => 'Check-in approchant',
                        MessageSequence::TRIGGER_POST_CHECKOUT        => 'Après le départ',
                        MessageSequence::TRIGGER_PRE_CHECKOUT         => 'Avant le départ',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMessageSequences::route('/'),
            'create' => Pages\CreateMessageSequence::route('/create'),
            'edit'   => Pages\EditMessageSequence::route('/{record}/edit'),
        ];
    }
}
