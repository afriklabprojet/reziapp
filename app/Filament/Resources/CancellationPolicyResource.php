<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CancellationPolicyResource\Pages;
use App\Models\CancellationPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CancellationPolicyResource extends Resource
{
    protected static ?string $model = CancellationPolicy::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Réservations';

    protected static ?string $navigationLabel = 'Politiques d\'annulation';

    protected static ?string $modelLabel = 'Politique d\'annulation';

    protected static ?string $pluralModelLabel = 'Politiques d\'annulation';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Par défaut'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Règles de remboursement')
                    ->schema([
                        Forms\Components\TextInput::make('full_refund_days')
                            ->label('Jours pour remboursement total')
                            ->numeric()
                            ->helperText('Nombre de jours avant arrivée'),
                        Forms\Components\TextInput::make('partial_refund_days')
                            ->label('Jours pour remboursement partiel')
                            ->numeric(),
                        Forms\Components\TextInput::make('partial_refund_percent')
                            ->label('% remboursement partiel')
                            ->numeric()
                            ->suffix('%'),
                        Forms\Components\TextInput::make('no_refund_days')
                            ->label('Jours sans remboursement')
                            ->numeric(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_refund_days')
                    ->label('Remb. total')
                    ->suffix(' jours'),
                Tables\Columns\TextColumn::make('partial_refund_percent')
                    ->label('Remb. partiel')
                    ->suffix('%'),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Par défaut')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCancellationPolicies::route('/'),
            'create' => Pages\CreateCancellationPolicy::route('/create'),
            'edit' => Pages\EditCancellationPolicy::route('/{record}/edit'),
        ];
    }
}
