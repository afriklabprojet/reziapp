<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarketPriceDataResource\Pages;
use App\Models\MarketPriceData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class MarketPriceDataResource extends Resource
{
    protected static ?string $model = MarketPriceData::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Analytiques';

    protected static ?string $navigationLabel = 'Prix du marché';

    protected static ?string $modelLabel = 'Prix du marché';

    protected static ?string $pluralModelLabel = 'Prix du marché';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false; // Données auto-générées
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Localisation')
                    ->schema([
                        Forms\Components\TextInput::make('country_code')
                            ->label('Pays')
                            ->disabled(),

                        Forms\Components\TextInput::make('city')
                            ->label('Ville')
                            ->disabled(),

                        Forms\Components\TextInput::make('commune')
                            ->label('Commune')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Type de bien')
                    ->schema([
                        Forms\Components\TextInput::make('residence_type')
                            ->label('Type de propriété')
                            ->disabled(),

                        Forms\Components\TextInput::make('bedrooms')
                            ->label('Nombre de chambres')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Prix (calculés automatiquement)')
                    ->schema([
                        Forms\Components\TextInput::make('avg_price_per_night')
                            ->label('Prix moyen/nuit')
                            ->suffix('FCFA')
                            ->disabled(),

                        Forms\Components\TextInput::make('min_price_per_night')
                            ->label('Prix minimum/nuit')
                            ->suffix('FCFA')
                            ->disabled(),

                        Forms\Components\TextInput::make('max_price_per_night')
                            ->label('Prix maximum/nuit')
                            ->suffix('FCFA')
                            ->disabled(),

                        Forms\Components\TextInput::make('median_price_per_night')
                            ->label('Prix médian/nuit')
                            ->suffix('FCFA')
                            ->disabled(),

                        Forms\Components\TextInput::make('sample_size')
                            ->label('Taille de l\'échantillon')
                            ->disabled()
                            ->helperText('Nombre de résidences analysées'),
                    ])->columns(3),

                Forms\Components\Section::make('Période d\'analyse')
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Début de période')
                            ->disabled(),

                        Forms\Components\DatePicker::make('period_end')
                            ->label('Fin de période')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('country_code')
                    ->label('Pays')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('commune')
                    ->label('Commune')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Toute la ville'),

                Tables\Columns\TextColumn::make('residence_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'apartment' => 'info',
                        'studio' => 'success',
                        'house' => 'warning',
                        'villa' => 'danger',
                        'room' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'apartment' => 'Appartement',
                        'studio' => 'Studio',
                        'house' => 'Maison',
                        'villa' => 'Villa',
                        'room' => 'Chambre',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('bedrooms')
                    ->label('Ch.')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('avg_price_per_night')
                    ->label('Moy./nuit')
                    ->money('XOF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('min_price_per_night')
                    ->label('Min')
                    ->money('XOF')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('max_price_per_night')
                    ->label('Max')
                    ->money('XOF')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('median_price_per_night')
                    ->label('Médian')
                    ->money('XOF')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sample_size')
                    ->label('Échantillon')
                    ->suffix(' résidences')
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Période')
                    ->date('M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country_code')
                    ->label('Pays')
                    ->options(fn () => \App\Models\Country::pluck('name', 'code')),

                Tables\Filters\SelectFilter::make('city')
                    ->label('Ville')
                    ->options(fn () => MarketPriceData::distinct()->pluck('city', 'city'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('residence_type')
                    ->label('Type')
                    ->options([
                        'apartment' => 'Appartement',
                        'studio' => 'Studio',
                        'house' => 'Maison',
                        'villa' => 'Villa',
                        'room' => 'Chambre',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('recalculate')
                    ->label('Recalculer les prix')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Recalculer les prix du marché')
                    ->modalDescription('Cette action va recalculer tous les prix du marché à partir des données des résidences actuelles. Continuer ?')
                    ->modalSubmitActionLabel('Recalculer')
                    ->action(function () {
                        try {
                            Artisan::call('rezi:calculate-market-prices', ['--country' => 'CI']);
                            
                            Notification::make()
                                ->title('Prix recalculés')
                                ->body('Les prix du marché ont été recalculés avec succès.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur')
                                ->body('Erreur lors du recalcul: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('Aucune donnée de prix')
            ->emptyStateDescription('Cliquez sur "Recalculer les prix" pour générer les données à partir des résidences.')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketPriceData::route('/'),
            'view' => Pages\ViewMarketPriceData::route('/{record}'),
        ];
    }
}
