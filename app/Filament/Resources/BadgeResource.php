<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BadgeResource\Pages;
use App\Models\Badge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BadgeResource extends Resource
{
    protected static ?string $model = Badge::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Utilisateurs';

    protected static ?string $modelLabel = 'Badge';

    protected static ?string $pluralModelLabel = 'Badges';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Badge')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('badge_type')
                            ->label('Type')
                            ->options([
                                Badge::TYPE_SUPERHOST         => 'Super Hôte',
                                Badge::TYPE_FAST_RESPONDER    => 'Réponse rapide',
                                Badge::TYPE_VERIFIED          => 'Vérifié',
                                Badge::TYPE_EXPERIENCED_HOST  => 'Hôte expérimenté',
                                Badge::TYPE_TOP_REVIEWER      => 'Top aviseur',
                                Badge::TYPE_TRUSTED_GUEST     => 'Locataire de confiance',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('earned_at')
                            ->label('Obtenu le')
                            ->required(),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expire le'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->badge()
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('badge_type')
                    ->badge()
                    ->label('Type')
                    ->colors([
                        'warning'  => Badge::TYPE_SUPERHOST,
                        'success'  => Badge::TYPE_VERIFIED,
                        'info'     => Badge::TYPE_FAST_RESPONDER,
                        'primary'  => Badge::TYPE_EXPERIENCED_HOST,
                        'gray'     => Badge::TYPE_TOP_REVIEWER,
                        'secondary' => Badge::TYPE_TRUSTED_GUEST,
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        Badge::TYPE_SUPERHOST        => 'Super Hôte',
                        Badge::TYPE_FAST_RESPONDER   => 'Réponse rapide',
                        Badge::TYPE_VERIFIED         => 'Vérifié',
                        Badge::TYPE_EXPERIENCED_HOST => 'Hôte expérimenté',
                        Badge::TYPE_TOP_REVIEWER     => 'Top aviseur',
                        Badge::TYPE_TRUSTED_GUEST    => 'Locataire de confiance',
                        default                      => $state,
                    }),
                Tables\Columns\TextColumn::make('earned_at')
                    ->badge()
                    ->label('Obtenu le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->badge()
                    ->label('Expire le')
                    ->dateTime('d/m/Y')
                    ->placeholder('Permanent'),
                Tables\Columns\TextColumn::make('created_at')
                    ->badge()
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('badge_type')
                    ->label('Type')
                    ->options([
                        Badge::TYPE_SUPERHOST         => 'Super Hôte',
                        Badge::TYPE_FAST_RESPONDER    => 'Réponse rapide',
                        Badge::TYPE_VERIFIED          => 'Vérifié',
                        Badge::TYPE_EXPERIENCED_HOST  => 'Hôte expérimenté',
                        Badge::TYPE_TOP_REVIEWER      => 'Top aviseur',
                        Badge::TYPE_TRUSTED_GUEST     => 'Locataire de confiance',
                    ]),
            ])
            ->defaultSort('earned_at', 'desc')
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
            'index'  => Pages\ListBadges::route('/'),
            'create' => Pages\CreateBadge::route('/create'),
            'edit'   => Pages\EditBadge::route('/{record}/edit'),
        ];
    }
}
