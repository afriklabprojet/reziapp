<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RentReminderResource\Pages;
use App\Models\RentReminder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RentReminderResource extends Resource
{
    protected static ?string $model = RentReminder::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Contrats & Cautions';

    protected static ?string $navigationLabel = 'Rappels de loyer';

    protected static ?string $modelLabel = 'Rappel de loyer';

    protected static ?string $pluralModelLabel = 'Rappels de loyer';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['tenant', 'residence']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rappel')
                    ->schema([
                        Forms\Components\Select::make('lease_contract_id')
                            ->label('Contrat de bail')
                            ->relationship('leaseContract', 'reference')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('tenant_id')
                            ->label('Locataire')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('residence_id')
                            ->label('Résidence')
                            ->relationship('residence', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(RentReminder::STATUSES)
                            ->required()
                            ->default(RentReminder::STATUS_PENDING),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Date d\'échéance')
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant (FCFA)')
                            ->numeric()
                            ->required(),
                        Forms\Components\DateTimePicker::make('sent_at')
                            ->label('Envoyé le'),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Payé le'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->badge()
                    ->label('Locataire')
                    ->searchable(),
                Tables\Columns\TextColumn::make('residence.title')
                    ->badge()
                    ->label('Résidence')
                    ->limit(20),
                Tables\Columns\TextColumn::make('due_date')
                    ->badge()
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->badge()
                    ->label('Montant')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ').' FCFA'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'warning' => RentReminder::STATUS_PENDING,
                        'primary' => RentReminder::STATUS_SENT,
                        'success' => RentReminder::STATUS_PAID,
                        'danger'  => RentReminder::STATUS_OVERDUE,
                    ])
                    ->formatStateUsing(fn ($s) => RentReminder::STATUSES[$s] ?? $s),
                Tables\Columns\TextColumn::make('sent_at')
                    ->badge()
                    ->label('Envoyé')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(RentReminder::STATUSES),
            ])
            ->defaultSort('due_date', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRentReminders::route('/'),
            'create' => Pages\CreateRentReminder::route('/create'),
            'edit'   => Pages\EditRentReminder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', RentReminder::STATUS_OVERDUE)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
