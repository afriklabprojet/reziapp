<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerBalanceResource\Pages;
use App\Models\OwnerBalance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OwnerBalanceResource extends Resource
{
    protected static ?string $model = OwnerBalance::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Gestion des hôtes';

    protected static ?string $navigationLabel = 'Soldes propriétaires';

    protected static ?string $modelLabel = 'Solde';

    protected static ?string $pluralModelLabel = 'Soldes propriétaires';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Propriétaire')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Propriétaire')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('role', 'owner')
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Soldes')
                    ->schema([
                        Forms\Components\TextInput::make('available_balance')
                            ->label('Solde disponible (FCFA)')
                            ->numeric()
                            ->prefix('FCFA')
                            ->default(0),
                        Forms\Components\TextInput::make('pending_balance')
                            ->label('Solde en attente (FCFA)')
                            ->numeric()
                            ->prefix('FCFA')
                            ->default(0),
                        Forms\Components\TextInput::make('total_earned')
                            ->label('Gains totaux (FCFA)')
                            ->numeric()
                            ->prefix('FCFA')
                            ->default(0),
                        Forms\Components\TextInput::make('total_withdrawn')
                            ->label('Retraits totaux (FCFA)')
                            ->numeric()
                            ->prefix('FCFA')
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Propriétaire')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('available_balance')
                    ->label('Disponible')
                    ->money('XOF')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('pending_balance')
                    ->label('En attente')
                    ->money('XOF')
                    ->sortable()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('total_earned')
                    ->label('Gains totaux')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_withdrawn')
                    ->label('Retraits')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('available_balance', 'desc')
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOwnerBalances::route('/'),
            'create' => Pages\CreateOwnerBalance::route('/create'),
            'edit' => Pages\EditOwnerBalance::route('/{record}/edit'),
        ];
    }
}
