<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CleaningTaskResource\Pages;
use App\Models\CleaningTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CleaningTaskResource extends Resource
{
    protected static ?string $model = CleaningTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Maintenance';

    protected static ?string $navigationLabel = 'Tâches de ménage';

    protected static ?string $modelLabel = 'Tâche de ménage';

    protected static ?string $pluralModelLabel = 'Tâches de ménage';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['residence', 'cleaner']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tâche')
                    ->schema([
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('booking_id')
                            ->label('Réservation')
                            ->relationship('booking', 'reference')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('cleaner_id')
                            ->label('Agent de ménage')
                            ->relationship('cleaner', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(CleaningTask::STATUSES)
                            ->required()
                            ->default(CleaningTask::STATUS_PENDING),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Planifié le')
                            ->required(),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Terminé le'),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Durée (minutes)')
                            ->numeric(),
                        Forms\Components\TextInput::make('cost')
                            ->label('Coût (FCFA)')
                            ->numeric(),
                    ])->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                        Forms\Components\Textarea::make('checklist')
                            ->label('Checklist (JSON)')
                            ->rows(3),
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
                    ->limit(25),
                Tables\Columns\TextColumn::make('cleaner.name')
                    ->badge()
                    ->label('Agent'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'warning' => CleaningTask::STATUS_PENDING,
                        'primary' => CleaningTask::STATUS_IN_PROGRESS,
                        'success' => [CleaningTask::STATUS_COMPLETED, CleaningTask::STATUS_VERIFIED],
                    ])
                    ->formatStateUsing(fn ($s) => CleaningTask::STATUSES[$s] ?? $s),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->badge()
                    ->label('Planifié')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->badge()
                    ->label('Terminé')
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('cost')
                    ->badge()
                    ->label('Coût')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ').' FCFA' : '-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(CleaningTask::STATUSES),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCleaningTasks::route('/'),
            'create' => Pages\CreateCleaningTask::route('/create'),
            'edit'   => Pages\EditCleaningTask::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', CleaningTask::STATUS_PENDING)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
