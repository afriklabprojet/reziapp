<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenanceRequestResource\Pages;
use App\Models\MaintenanceRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceRequestResource extends Resource
{
    protected static ?string $model = MaintenanceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Maintenance';

    protected static ?string $navigationLabel = 'Demandes de maintenance';

    protected static ?string $modelLabel = 'Demande';

    protected static ?string $pluralModelLabel = 'Demandes de maintenance';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['residence', 'requester']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Demande')
                    ->schema([
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('requester_id')
                            ->label('Demandeur')
                            ->relationship('requester', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options(MaintenanceRequest::CATEGORIES)
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->label('Priorité')
                            ->options(MaintenanceRequest::PRIORITIES)
                            ->required()
                            ->default(MaintenanceRequest::PRIORITY_MEDIUM),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(MaintenanceRequest::STATUSES)
                            ->required()
                            ->default(MaintenanceRequest::STATUS_REPORTED),
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(4),
                        Forms\Components\TextInput::make('estimated_cost')
                            ->label('Coût estimé (FCFA)')
                            ->numeric(),
                        Forms\Components\TextInput::make('actual_cost')
                            ->label('Coût réel (FCFA)')
                            ->numeric(),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Planifié le'),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Complété le'),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes admin')
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
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('title')
                    ->badge()
                    ->label('Titre')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->label('Catégorie')
                    ->formatStateUsing(fn ($s) => MaintenanceRequest::CATEGORIES[$s] ?? $s),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->label('Priorité')
                    ->colors([
                        'success' => MaintenanceRequest::PRIORITY_LOW,
                        'primary' => MaintenanceRequest::PRIORITY_MEDIUM,
                        'warning' => MaintenanceRequest::PRIORITY_HIGH,
                        'danger'  => MaintenanceRequest::PRIORITY_URGENT,
                    ])
                    ->formatStateUsing(fn ($s) => MaintenanceRequest::PRIORITIES[$s] ?? $s),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'warning' => MaintenanceRequest::STATUS_REPORTED,
                        'primary' => [MaintenanceRequest::STATUS_ACKNOWLEDGED, MaintenanceRequest::STATUS_IN_PROGRESS],
                        'success' => [MaintenanceRequest::STATUS_RESOLVED, MaintenanceRequest::STATUS_CLOSED],
                    ])
                    ->formatStateUsing(fn ($s) => MaintenanceRequest::STATUSES[$s] ?? $s),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->badge()
                    ->label('Planifié')
                    ->date('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(MaintenanceRequest::STATUSES),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorité')
                    ->options(MaintenanceRequest::PRIORITIES),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(MaintenanceRequest::CATEGORIES),
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
            'index'  => Pages\ListMaintenanceRequests::route('/'),
            'create' => Pages\CreateMaintenanceRequest::route('/create'),
            'edit'   => Pages\EditMaintenanceRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::whereIn('status', [
            MaintenanceRequest::STATUS_REPORTED,
            MaintenanceRequest::STATUS_ACKNOWLEDGED,
        ])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
