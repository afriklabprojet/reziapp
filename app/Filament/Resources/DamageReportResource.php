<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DamageReportResource\Pages;
use App\Models\DamageReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DamageReportResource extends Resource
{
    protected static ?string $model = DamageReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static ?string $navigationGroup = 'Maintenance';

    protected static ?string $navigationLabel = 'Rapports de dégâts';

    protected static ?string $modelLabel = 'Rapport de dégât';

    protected static ?string $pluralModelLabel = 'Rapports de dégâts';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['reporter', 'residence']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rapport')
                    ->schema([
                        Forms\Components\Select::make('reporter_id')
                            ->label('Signalé par')
                            ->relationship('reporter', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options(DamageReport::CATEGORIES)
                            ->required(),
                        Forms\Components\Select::make('severity')
                            ->label('Gravité')
                            ->options(DamageReport::SEVERITIES)
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(DamageReport::STATUSES)
                            ->required()
                            ->default(DamageReport::STATUS_REPORTED),
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        Forms\Components\TextInput::make('estimated_cost')
                            ->label('Coût estimé (FCFA)')
                            ->numeric(),
                        Forms\Components\TextInput::make('actual_repair_cost')
                            ->label('Coût réel (FCFA)')
                            ->numeric(),
                        Forms\Components\TextInput::make('deduction_amount')
                            ->label('Montant déduit (FCFA)')
                            ->numeric(),
                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Résolu le'),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Notes de résolution')
                            ->rows(2),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->badge()
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('residence.title')
                    ->badge()
                    ->label('Résidence')
                    ->limit(25),
                Tables\Columns\TextColumn::make('title')
                    ->badge()
                    ->label('Titre')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->label('Catégorie')
                    ->formatStateUsing(fn ($s) => DamageReport::CATEGORIES[$s] ?? $s),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->label('Gravité')
                    ->colors([
                        'success' => DamageReport::SEVERITY_MINOR,
                        'warning' => DamageReport::SEVERITY_MODERATE,
                        'danger'  => [DamageReport::SEVERITY_MAJOR, DamageReport::SEVERITY_CRITICAL],
                    ])
                    ->formatStateUsing(fn ($s) => DamageReport::SEVERITIES[$s] ?? $s),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'warning' => DamageReport::STATUS_REPORTED,
                        'primary' => DamageReport::STATUS_ASSESSED,
                        'info'    => DamageReport::STATUS_REPAIR_SCHEDULED,
                        'success' => [DamageReport::STATUS_REPAIRED, DamageReport::STATUS_DEDUCTED],
                    ])
                    ->formatStateUsing(fn ($s) => DamageReport::STATUSES[$s] ?? $s),
                Tables\Columns\TextColumn::make('estimated_cost')
                    ->badge()
                    ->label('Estimé')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ').' FCFA' : '-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(DamageReport::STATUSES),
                Tables\Filters\SelectFilter::make('severity')
                    ->label('Gravité')
                    ->options(DamageReport::SEVERITIES),
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
            'index'  => Pages\ListDamageReports::route('/'),
            'create' => Pages\CreateDamageReport::route('/create'),
            'edit'   => Pages\EditDamageReport::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::whereIn('status', [
            DamageReport::STATUS_REPORTED,
            DamageReport::STATUS_ASSESSED,
        ])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
