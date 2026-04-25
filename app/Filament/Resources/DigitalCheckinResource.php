<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DigitalCheckinResource\Pages;
use App\Models\DigitalCheckin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DigitalCheckinResource extends Resource
{
    protected static ?string $model = DigitalCheckin::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationGroup = 'Réservations';

    protected static ?string $navigationLabel = 'Check-ins digitaux';

    protected static ?string $modelLabel = 'Check-in digital';

    protected static ?string $pluralModelLabel = 'Check-ins digitaux';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['guest', 'residence']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Check-in')
                    ->schema([
                        Forms\Components\Select::make('booking_id')
                            ->label('Réservation')
                            ->relationship('booking', 'reference')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('guest_id')
                            ->label('Voyageur')
                            ->relationship('guest', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'check_in'  => 'Arrivée',
                                'check_out' => 'Départ',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending'   => 'En attente',
                                'confirmed' => 'Confirmé',
                                'expired'   => 'Expiré',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\TextInput::make('qr_token')
                            ->label('Token QR')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Forms\Components\Section::make('Confirmation')
                    ->schema([
                        Forms\Components\DateTimePicker::make('confirmed_at')
                            ->label('Confirmé le'),
                        Forms\Components\TextInput::make('confirmed_by')
                            ->label('Confirmé par'),
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric(),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking.reference')
                    ->badge()
                    ->label('Réservation')
                    ->searchable(),
                Tables\Columns\TextColumn::make('residence.title')
                    ->badge()
                    ->label('Résidence')
                    ->limit(25),
                Tables\Columns\TextColumn::make('guest.name')
                    ->badge()
                    ->label('Voyageur'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Type')
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'check_in'  => 'Arrivée',
                        'check_out' => 'Départ',
                        default     => $s,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger'  => 'expired',
                    ])
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'pending'   => 'En attente',
                        'confirmed' => 'Confirmé',
                        'expired'   => 'Expiré',
                        default     => $s,
                    }),
                Tables\Columns\TextColumn::make('confirmed_at')
                    ->badge()
                    ->label('Confirmé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending'   => 'En attente',
                        'confirmed' => 'Confirmé',
                        'expired'   => 'Expiré',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'check_in'  => 'Arrivée',
                        'check_out' => 'Départ',
                    ]),
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
            'index'  => Pages\ListDigitalCheckins::route('/'),
            'create' => Pages\CreateDigitalCheckin::route('/create'),
            'edit'   => Pages\EditDigitalCheckin::route('/{record}/edit'),
        ];
    }
}
