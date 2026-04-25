<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResidenceVerificationResource\Pages;
use App\Models\ResidenceVerification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResidenceVerificationResource extends Resource
{
    protected static ?string $model = ResidenceVerification::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Modération';

    protected static ?string $navigationLabel = 'Vérifications résidences';

    protected static ?string $modelLabel = 'Vérification';

    protected static ?string $pluralModelLabel = 'Vérifications de résidences';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['residence', 'user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Résidence')
                    ->schema([
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Soumis par')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('verification_type')
                            ->label('Type de vérification')
                            ->options([
                                'document' => 'Document',
                                'visit'    => 'Visite',
                                'gps'      => 'GPS',
                            ]),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending'   => 'En attente',
                                'approved'  => 'Approuvé',
                                'rejected'  => 'Refusé',
                                'scheduled' => 'Visite planifiée',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Visite')
                    ->schema([
                        Forms\Components\DateTimePicker::make('visit_scheduled_at')
                            ->label('Visite planifiée le'),
                        Forms\Components\DateTimePicker::make('visit_completed_at')
                            ->label('Visite réalisée le'),
                        Forms\Components\Textarea::make('visit_notes')
                            ->label('Notes de visite')
                            ->rows(3),
                    ])->columns(2),

                Forms\Components\Section::make('Décision admin')
                    ->schema([
                        Forms\Components\Select::make('reviewed_by')
                            ->label('Revu par')
                            ->relationship('reviewer', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('reviewed_at')
                            ->label('Revu le'),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif de refus')
                            ->rows(2),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes admin')
                            ->rows(2),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expire le'),
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
                    ->limit(30),
                Tables\Columns\TextColumn::make('user.name')
                    ->badge()
                    ->label('Soumis par'),
                Tables\Columns\TextColumn::make('verification_type')
                    ->badge()
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'document' => 'Document',
                        'visit'    => 'Visite',
                        'gps'      => 'GPS',
                        default    => $s,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'warning' => ['pending', 'scheduled'],
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ])
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'pending'   => 'En attente',
                        'approved'  => 'Approuvé',
                        'rejected'  => 'Refusé',
                        'scheduled' => 'Planifiée',
                        default     => $s,
                    }),
                Tables\Columns\TextColumn::make('visit_scheduled_at')
                    ->badge()
                    ->label('Visite planifiée')
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->badge()
                    ->label('Revu le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending'   => 'En attente',
                        'approved'  => 'Approuvé',
                        'rejected'  => 'Refusé',
                        'scheduled' => 'Visite planifiée',
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
            'index'  => Pages\ListResidenceVerifications::route('/'),
            'create' => Pages\CreateResidenceVerification::route('/create'),
            'edit'   => Pages\EditResidenceVerification::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
