<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlatformSettingResource\Pages;
use App\Models\PlatformSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlatformSettingResource extends Resource
{
    protected static ?string $model = PlatformSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Paramètres';

    protected static ?string $modelLabel = 'Paramètre';

    protected static ?string $pluralModelLabel = 'Paramètres';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Paramètre')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Clé')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('label')
                            ->label('Libellé')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('group')
                            ->label('Groupe')
                            ->maxLength(100),
                        Forms\Components\Select::make('type')
                            ->label('Type de valeur')
                            ->options([
                                'string'  => 'Texte',
                                'integer' => 'Entier',
                                'float'   => 'Décimal',
                                'boolean' => 'Booléen',
                                'json'    => 'JSON',
                            ])
                            ->required()
                            ->default('string'),
                        Forms\Components\Textarea::make('value')
                            ->label('Valeur')
                            ->rows(3),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2),
                        Forms\Components\Toggle::make('is_public')
                            ->label('Visible publiquement')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Groupe')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('key')
                    ->label('Clé')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valeur')
                    ->limit(40),
                Tables\Columns\IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Groupe')
                    ->options(fn () => PlatformSetting::distinct()->pluck('group', 'group')->filter()->toArray()),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'string'  => 'Texte',
                        'integer' => 'Entier',
                        'float'   => 'Décimal',
                        'boolean' => 'Booléen',
                        'json'    => 'JSON',
                    ]),
            ])
            ->defaultSort('group')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlatformSettings::route('/'),
            'create' => Pages\CreatePlatformSetting::route('/create'),
            'edit'   => Pages\EditPlatformSetting::route('/{record}/edit'),
        ];
    }
}
