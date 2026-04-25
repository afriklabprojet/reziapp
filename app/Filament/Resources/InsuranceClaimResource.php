<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InsuranceClaimResource\Pages;
use App\Models\InsuranceClaim;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InsuranceClaimResource extends Resource
{
    protected static ?string $model = InsuranceClaim::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Assurances';

    protected static ?string $navigationLabel = 'Sinistres';

    protected static ?string $modelLabel = 'Sinistre';

    protected static ?string $pluralModelLabel = 'Sinistres';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'claim_number';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Déclarant & Sinistre')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Déclarant')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('claim_number')
                            ->label('Numéro de sinistre')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('claim_type')
                            ->label('Type de sinistre')
                            ->options([
                                'theft'    => 'Vol',
                                'damage'   => 'Dégât',
                                'accident' => 'Accident',
                                'fire'     => 'Incendie',
                                'flood'    => 'Inondation',
                                'other'    => 'Autre',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending'      => 'En attente',
                                'under_review' => 'En cours d\'analyse',
                                'approved'     => 'Approuvé',
                                'rejected'     => 'Refusé',
                                'paid'         => 'Remboursé',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('incident_date')
                            ->label('Date de l\'incident'),
                    ])->columns(2),

                Forms\Components\Section::make('Montants')
                    ->schema([
                        Forms\Components\TextInput::make('claimed_amount')
                            ->label('Montant déclaré (FCFA)')
                            ->numeric(),
                        Forms\Components\TextInput::make('approved_amount')
                            ->label('Montant approuvé (FCFA)')
                            ->numeric(),
                        Forms\Components\DateTimePicker::make('reviewed_at')
                            ->label('Revu le'),
                    ])->columns(3),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(4),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes admin')
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('claim_number')
                    ->badge()
                    ->label('N° Sinistre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->badge()
                    ->label('Déclarant')
                    ->searchable(),
                Tables\Columns\TextColumn::make('claim_type')
                    ->badge()
                    ->label('Type')
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'theft'    => 'Vol',
                        'damage'   => 'Dégât',
                        'accident' => 'Accident',
                        'fire'     => 'Incendie',
                        'flood'    => 'Inondation',
                        default    => 'Autre',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'warning' => ['pending', 'under_review'],
                        'success' => ['approved', 'paid'],
                        'danger'  => 'rejected',
                    ])
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'pending'      => 'En attente',
                        'under_review' => 'En analyse',
                        'approved'     => 'Approuvé',
                        'rejected'     => 'Refusé',
                        'paid'         => 'Remboursé',
                        default        => $s,
                    }),
                Tables\Columns\TextColumn::make('claimed_amount')
                    ->badge()
                    ->label('Déclaré')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ').' FCFA' : '-'),
                Tables\Columns\TextColumn::make('approved_amount')
                    ->badge()
                    ->label('Approuvé')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ').' FCFA' : '-'),
                Tables\Columns\TextColumn::make('incident_date')
                    ->badge()
                    ->label('Date incident')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending'      => 'En attente',
                        'under_review' => 'En cours d\'analyse',
                        'approved'     => 'Approuvé',
                        'rejected'     => 'Refusé',
                        'paid'         => 'Remboursé',
                    ]),
                Tables\Filters\SelectFilter::make('claim_type')
                    ->label('Type')
                    ->options([
                        'theft'    => 'Vol',
                        'damage'   => 'Dégât',
                        'accident' => 'Accident',
                        'fire'     => 'Incendie',
                        'flood'    => 'Inondation',
                        'other'    => 'Autre',
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
            'index'  => Pages\ListInsuranceClaims::route('/'),
            'create' => Pages\CreateInsuranceClaim::route('/create'),
            'edit'   => Pages\EditInsuranceClaim::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::whereIn('status', ['pending', 'under_review'])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
