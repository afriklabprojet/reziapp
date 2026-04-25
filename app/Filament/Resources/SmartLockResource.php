<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmartLockResource\Pages;
use App\Models\SmartLock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SmartLockResource extends Resource
{
    protected static ?string $model = SmartLock::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $modelLabel = 'Serrure connectée';

    protected static ?string $pluralModelLabel = 'Serrures connectées';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Serrure')
                    ->schema([
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Propriétaire')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('provider')
                            ->label('Fournisseur')
                            ->options(SmartLock::PROVIDERS)
                            ->required(),
                        Forms\Components\TextInput::make('device_id')
                            ->label('ID appareil')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('device_name')
                            ->label('Nom appareil')
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                SmartLock::STATUS_ACTIVE   => 'Actif',
                                SmartLock::STATUS_INACTIVE => 'Inactif',
                                SmartLock::STATUS_OFFLINE  => 'Hors ligne',
                                SmartLock::STATUS_ERROR    => 'Erreur',
                            ])
                            ->default(SmartLock::STATUS_ACTIVE),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('residence.title')
                    ->badge()
                    ->label('Résidence')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('device_name')
                    ->badge()
                    ->label('Appareil')
                    ->searchable()
                    ->default('—'),
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->label('Fournisseur')
                    ->formatStateUsing(fn (string $state) => SmartLock::PROVIDERS[$state] ?? $state),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'success' => SmartLock::STATUS_ACTIVE,
                        'gray'    => SmartLock::STATUS_INACTIVE,
                        'warning' => SmartLock::STATUS_OFFLINE,
                        'danger'  => SmartLock::STATUS_ERROR,
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        SmartLock::STATUS_ACTIVE   => 'Actif',
                        SmartLock::STATUS_INACTIVE => 'Inactif',
                        SmartLock::STATUS_OFFLINE  => 'Hors ligne',
                        SmartLock::STATUS_ERROR    => 'Erreur',
                        default                    => $state,
                    }),
                Tables\Columns\TextColumn::make('last_synced_at')
                    ->badge()
                    ->label('Dernière synchro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Jamais'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->label('Fournisseur')
                    ->options(SmartLock::PROVIDERS),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        SmartLock::STATUS_ACTIVE   => 'Actif',
                        SmartLock::STATUS_INACTIVE => 'Inactif',
                        SmartLock::STATUS_OFFLINE  => 'Hors ligne',
                        SmartLock::STATUS_ERROR    => 'Erreur',
                    ]),
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
            'index'  => Pages\ListSmartLocks::route('/'),
            'create' => Pages\CreateSmartLock::route('/create'),
            'edit'   => Pages\EditSmartLock::route('/{record}/edit'),
        ];
    }
}
