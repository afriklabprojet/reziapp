<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyInspectionResource\Pages;
use App\Models\PropertyInspection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PropertyInspectionResource extends Resource
{
    protected static ?string $model = PropertyInspection::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Maintenance';

    protected static ?string $navigationLabel = 'États des lieux';

    protected static ?string $modelLabel = 'État des lieux';

    protected static ?string $pluralModelLabel = 'États des lieux';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['residence', 'inspector']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Inspection')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('booking_id')
                            ->label('Réservation')
                            ->relationship('booking', 'reference')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('inspector_id')
                            ->label('Inspecteur')
                            ->relationship('inspector', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                PropertyInspection::TYPE_CHECK_IN  => 'État des lieux d\'entrée',
                                PropertyInspection::TYPE_CHECK_OUT => 'État des lieux de sortie',
                                PropertyInspection::TYPE_PERIODIC  => 'Visite périodique',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                PropertyInspection::STATUS_DRAFT       => 'Brouillon',
                                PropertyInspection::STATUS_IN_PROGRESS => 'En cours',
                                PropertyInspection::STATUS_COMPLETED   => 'Complété',
                                PropertyInspection::STATUS_SIGNED      => 'Signé',
                            ])
                            ->required()
                            ->default(PropertyInspection::STATUS_DRAFT),
                    ])->columns(2),

                Forms\Components\Section::make('Déroulement')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Planifié le'),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Complété le'),
                        Forms\Components\TextInput::make('overall_condition')
                            ->label('Condition générale')
                            ->maxLength(100),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(4),
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
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Type')
                    ->formatStateUsing(fn ($s) => match ($s) {
                        PropertyInspection::TYPE_CHECK_IN  => 'Entrée',
                        PropertyInspection::TYPE_CHECK_OUT => 'Sortie',
                        PropertyInspection::TYPE_PERIODIC  => 'Périodique',
                        default                            => $s,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'gray'    => PropertyInspection::STATUS_DRAFT,
                        'primary' => PropertyInspection::STATUS_IN_PROGRESS,
                        'success' => [PropertyInspection::STATUS_COMPLETED, PropertyInspection::STATUS_SIGNED],
                    ])
                    ->formatStateUsing(fn ($s) => match ($s) {
                        PropertyInspection::STATUS_DRAFT       => 'Brouillon',
                        PropertyInspection::STATUS_IN_PROGRESS => 'En cours',
                        PropertyInspection::STATUS_COMPLETED   => 'Complété',
                        PropertyInspection::STATUS_SIGNED      => 'Signé',
                        default                                => $s,
                    }),
                Tables\Columns\TextColumn::make('inspector.name')
                    ->badge()
                    ->label('Inspecteur'),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->badge()
                    ->label('Planifié')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->badge()
                    ->label('Complété')
                    ->date('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        PropertyInspection::TYPE_CHECK_IN  => 'Entrée',
                        PropertyInspection::TYPE_CHECK_OUT => 'Sortie',
                        PropertyInspection::TYPE_PERIODIC  => 'Périodique',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        PropertyInspection::STATUS_DRAFT       => 'Brouillon',
                        PropertyInspection::STATUS_IN_PROGRESS => 'En cours',
                        PropertyInspection::STATUS_COMPLETED   => 'Complété',
                        PropertyInspection::STATUS_SIGNED      => 'Signé',
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
            'index'  => Pages\ListPropertyInspections::route('/'),
            'create' => Pages\CreatePropertyInspection::route('/create'),
            'edit'   => Pages\EditPropertyInspection::route('/{record}/edit'),
        ];
    }
}
