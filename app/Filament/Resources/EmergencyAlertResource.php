<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmergencyAlertResource\Pages;
use App\Models\EmergencyAlert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EmergencyAlertResource extends Resource
{
    protected static ?string $model = EmergencyAlert::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Sécurité';

    protected static ?string $navigationLabel = 'Alertes urgence';

    protected static ?string $modelLabel = 'Alerte urgence';

    protected static ?string $pluralModelLabel = 'Alertes d\'urgence';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Utilisateur & Localisation')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('alert_type')
                            ->label('Type d\'alerte')
                            ->options([
                                'panic' => 'Bouton panique',
                                'sos' => 'SOS',
                                'check_in_missed' => 'Check-in manqué',
                                'suspicious' => 'Situation suspecte',
                                'medical' => 'Urgence médicale',
                                'other' => 'Autre',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric(),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric(),
                        Forms\Components\TextInput::make('address')
                            ->label('Adresse')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'triggered' => 'Déclenché',
                                'notified' => 'Contacts notifiés',
                                'acknowledged' => 'Pris en charge',
                                'resolved' => 'Résolu',
                                'false_alarm' => 'Fausse alerte',
                            ])
                            ->required()
                            ->native(false),
                    ]),

                Forms\Components\Section::make('Résolution')
                    ->schema([
                        Forms\Components\Select::make('resolved_by')
                            ->label('Résolu par')
                            ->relationship('resolver', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Résolu le'),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Notes de résolution')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('alert_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'panic', 'sos' => 'danger',
                        'medical' => 'warning',
                        'check_in_missed' => 'info',
                        'suspicious' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'panic' => '🚨 Panique',
                        'sos' => '🆘 SOS',
                        'check_in_missed' => 'Check-in manqué',
                        'suspicious' => 'Suspect',
                        'medical' => '🏥 Médical',
                        'other' => 'Autre',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'triggered' => 'danger',
                        'notified' => 'warning',
                        'acknowledged' => 'info',
                        'resolved' => 'success',
                        'false_alarm' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'triggered' => 'Déclenché',
                        'notified' => 'Notifié',
                        'acknowledged' => 'Pris en charge',
                        'resolved' => 'Résolu',
                        'false_alarm' => 'Fausse alerte',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('address')
                    ->label('Localisation')
                    ->limit(30)
                    ->placeholder('—')
                    ->tooltip(function (EmergencyAlert $record): ?string {
                        if ($record->latitude && $record->longitude) {
                            return "GPS: {$record->latitude}, {$record->longitude}";
                        }

                        return null;
                    }),
                Tables\Columns\TextColumn::make('resolver.name')
                    ->label('Résolu par')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Déclenchée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Résolue le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'triggered' => 'Déclenché',
                        'notified' => 'Contacts notifiés',
                        'acknowledged' => 'Pris en charge',
                        'resolved' => 'Résolu',
                        'false_alarm' => 'Fausse alerte',
                    ]),
                Tables\Filters\SelectFilter::make('alert_type')
                    ->label('Type')
                    ->options([
                        'panic' => 'Bouton panique',
                        'sos' => 'SOS',
                        'check_in_missed' => 'Check-in manqué',
                        'suspicious' => 'Situation suspecte',
                        'medical' => 'Urgence médicale',
                        'other' => 'Autre',
                    ]),
                Tables\Filters\Filter::make('active')
                    ->label('Actives uniquement')
                    ->query(fn ($query) => $query->active())
                    ->default(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),

                // Prendre en charge
                Tables\Actions\Action::make('acknowledge')
                    ->label('Prendre en charge')
                    ->icon('heroicon-o-hand-raised')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Prendre en charge cette alerte ?')
                    ->visible(fn (EmergencyAlert $record): bool => in_array($record->status, ['triggered', 'notified']))
                    ->action(function (EmergencyAlert $record): void {
                        $record->acknowledge();
                    }),

                // Résoudre
                Tables\Actions\Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (EmergencyAlert $record): bool => in_array($record->status, ['triggered', 'notified', 'acknowledged']))
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes de résolution')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000),
                        Forms\Components\Toggle::make('false_alarm')
                            ->label('Fausse alerte')
                            ->default(false),
                    ])
                    ->action(function (EmergencyAlert $record, array $data): void {
                        $record->resolve(
                            Auth::id(),
                            $data['notes'],
                            $data['false_alarm'] ?? false,
                        );
                    }),

                // Voir sur Google Maps
                Tables\Actions\Action::make('map')
                    ->label('Carte')
                    ->icon('heroicon-o-map-pin')
                    ->color('gray')
                    ->visible(fn (EmergencyAlert $record): bool => $record->latitude && $record->longitude)
                    ->url(fn (EmergencyAlert $record): string => "https://maps.google.com/?q={$record->latitude},{$record->longitude}")
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmergencyAlerts::route('/'),
            'view' => Pages\ViewEmergencyAlert::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false; // Les alertes sont créées par les utilisateurs, pas par l'admin
    }
}
