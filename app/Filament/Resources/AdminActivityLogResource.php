<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminActivityLogResource\Pages;
use App\Models\AdminActivityLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdminActivityLogResource extends Resource
{
    protected static ?string $model = AdminActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Journal d\'activité';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'Activité';

    protected static ?string $pluralModelLabel = 'Journal d\'activité';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Détails de l\'activité')
                    ->schema([
                        Forms\Components\TextInput::make('admin.name')
                            ->label('Administrateur'),
                        Forms\Components\TextInput::make('action')
                            ->label('Action')
                            ->formatStateUsing(fn ($state) => AdminActivityLog::getActionLabels()[$state] ?? $state),
                        Forms\Components\TextInput::make('description')
                            ->label('Description'),
                        Forms\Components\TextInput::make('model_type')
                            ->label('Type de modèle'),
                        Forms\Components\TextInput::make('model_id')
                            ->label('ID du modèle'),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('Adresse IP'),
                        Forms\Components\Textarea::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('old_values')
                            ->label('Anciennes valeurs')
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('new_values')
                            ->label('Nouvelles valeurs')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Admin')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn ($state) => AdminActivityLog::getActionLabels()[$state] ?? $state)
                    ->color(fn ($record) => $record->getActionColor()),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description)
                    ->searchable(),
                Tables\Columns\TextColumn::make('model_type')
                    ->label('Modèle')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('model_id')
                    ->label('ID')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Action')
                    ->options(AdminActivityLog::getActionLabels()),
                Tables\Filters\SelectFilter::make('admin_id')
                    ->label('Administrateur')
                    ->options(
                        User::where('role', 'admin')
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable(),
                Tables\Filters\SelectFilter::make('model_type')
                    ->label('Type de modèle')
                    ->options([
                        'App\Models\User' => 'Utilisateur',
                        'App\Models\Residence' => 'Résidence',
                        'App\Models\Booking' => 'Réservation',
                        'App\Models\Payment' => 'Paiement',
                        'App\Models\Review' => 'Avis',
                        'App\Models\FraudReport' => 'Signalement',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Pas de bulk actions pour les logs
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminActivityLogs::route('/'),
            'view' => Pages\ViewAdminActivityLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
