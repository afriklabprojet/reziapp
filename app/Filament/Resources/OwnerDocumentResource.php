<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerDocumentResource\Pages;
use App\Models\OwnerDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OwnerDocumentResource extends Resource
{
    protected static ?string $model = OwnerDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationGroup = 'Propriétaires';

    protected static ?string $navigationLabel = 'Documents propriétaires';

    protected static ?string $modelLabel = 'Document';

    protected static ?string $pluralModelLabel = 'Documents propriétaires';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['owner', 'residence']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\Select::make('owner_id')
                            ->label('Propriétaire')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence concernée')
                            ->relationship('residence', 'title')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'title_deed' => 'Titre de propriété',
                                'permit'     => 'Permis',
                                'insurance'  => 'Assurance',
                                'tax'        => 'Fiscal',
                                'contract'   => 'Contrat',
                                'other'      => 'Autre',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du document')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('file_path')
                            ->label('Chemin du fichier')
                            ->maxLength(500),
                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Date d\'expiration'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('residence.title')
                    ->label('Résidence')
                    ->limit(25),
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'title_deed' => 'Titre de propriété',
                        'permit'     => 'Permis',
                        'insurance'  => 'Assurance',
                        'tax'        => 'Fiscal',
                        'contract'   => 'Contrat',
                        'other'      => 'Autre',
                        default      => $s,
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->expiry_date && $record->expiry_date < now() ? 'danger' : null),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ajouté le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'title_deed' => 'Titre de propriété',
                        'permit'     => 'Permis',
                        'insurance'  => 'Assurance',
                        'tax'        => 'Fiscal',
                        'contract'   => 'Contrat',
                        'other'      => 'Autre',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOwnerDocuments::route('/'),
            'create' => Pages\CreateOwnerDocument::route('/create'),
            'edit'   => Pages\EditOwnerDocument::route('/{record}/edit'),
        ];
    }
}
