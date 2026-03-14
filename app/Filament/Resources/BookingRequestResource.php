<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingRequestResource\Pages;
use App\Models\BookingRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingRequestResource extends Resource
{
    protected static ?string $model = BookingRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Réservations';

    protected static ?string $navigationLabel = 'Historique actions';

    protected static ?string $modelLabel = 'Action';

    protected static ?string $pluralModelLabel = 'Historique des actions';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\Select::make('booking_id')
                            ->label('Réservation')
                            ->relationship('booking', 'reference')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('action_by')
                            ->label('Action par')
                            ->relationship('actionBy', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('action')
                            ->label('Action')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->rows(2),
                        Forms\Components\Textarea::make('reason')
                            ->label('Raison')
                            ->rows(2),
                        Forms\Components\KeyValue::make('changes')
                            ->label('Modifications'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking.reference')
                    ->label('Réservation')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'created' => 'success',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'modified' => 'warning',
                        'payment_received' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('actionBy.name')
                    ->label('Par')
                    ->placeholder('Système'),
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(40)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'created' => 'Créée',
                        'confirmed' => 'Confirmée',
                        'cancelled' => 'Annulée',
                        'modified' => 'Modifiée',
                        'payment_received' => 'Paiement reçu',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
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
            'index' => Pages\ListBookingRequests::route('/'),
            'create' => Pages\CreateBookingRequest::route('/create'),
            'edit' => Pages\EditBookingRequest::route('/{record}/edit'),
        ];
    }
}
