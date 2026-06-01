<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?string $navigationLabel = 'Dépenses';

    protected static ?string $modelLabel = 'Dépense';

    protected static ?string $pluralModelLabel = 'Dépenses';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['owner', 'residence']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dépense')
                    ->schema([
                        Forms\Components\Select::make('owner_id')
                            ->label('Propriétaire')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'maintenance'    => 'Maintenance',
                                'cleaning'       => 'Ménage',
                                'utilities'      => 'Charges',
                                'insurance'      => 'Assurance',
                                'furniture'      => 'Mobilier',
                                'marketing'      => 'Marketing',
                                'commission'     => 'Commission',
                                'tax'            => 'Impôts/Taxes',
                                'other'          => 'Autre',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('label')
                            ->label('Libellé')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant (FCFA)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('currency')
                            ->label('Devise')
                            ->default('XOF')
                            ->maxLength(10),
                        Forms\Components\DatePicker::make('expense_date')
                            ->label('Date de dépense')
                            ->required(),
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Récurrent')
                            ->default(false),
                        Forms\Components\Select::make('recurring_frequency')
                            ->label('Fréquence')
                            ->options([
                                'weekly'    => 'Hebdomadaire',
                                'monthly'   => 'Mensuel',
                                'quarterly' => 'Trimestriel',
                                'yearly'    => 'Annuel',
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire')
                    ->searchable(),
                Tables\Columns\TextColumn::make('residence.title')
                    ->label('Résidence')
                    ->limit(20),
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'maintenance' => 'Maintenance',
                        'cleaning'    => 'Ménage',
                        'utilities'   => 'Charges',
                        'insurance'   => 'Assurance',
                        'furniture'   => 'Mobilier',
                        'marketing'   => 'Marketing',
                        'commission'  => 'Commission',
                        'tax'         => 'Impôts/Taxes',
                        default       => 'Autre',
                    }),
                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ').' FCFA')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expense_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('Récurrent')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'maintenance' => 'Maintenance',
                        'cleaning'    => 'Ménage',
                        'utilities'   => 'Charges',
                        'insurance'   => 'Assurance',
                        'furniture'   => 'Mobilier',
                        'marketing'   => 'Marketing',
                        'commission'  => 'Commission',
                        'tax'         => 'Impôts/Taxes',
                        'other'       => 'Autre',
                    ]),
                Tables\Filters\TernaryFilter::make('is_recurring')
                    ->label('Récurrent'),
            ])
            ->defaultSort('expense_date', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit'   => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
