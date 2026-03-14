<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Support';

    protected static ?string $navigationLabel = 'Tickets';

    protected static ?string $modelLabel = 'Ticket';

    protected static ?string $pluralModelLabel = 'Tickets';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du ticket')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('subject')
                            ->label('Sujet')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'booking' => 'Réservation',
                                'payment' => 'Paiement',
                                'account' => 'Compte',
                                'residence' => 'Résidence',
                                'technical' => 'Technique',
                                'other' => 'Autre',
                            ])
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->label('Priorité')
                            ->options([
                                'low' => 'Basse',
                                'medium' => 'Moyenne',
                                'high' => 'Haute',
                                'urgent' => 'Urgente',
                            ])
                            ->default('medium')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'open' => 'Ouvert',
                                'in_progress' => 'En cours',
                                'waiting' => 'En attente',
                                'resolved' => 'Résolu',
                                'closed' => 'Fermé',
                            ])
                            ->default('open')
                            ->required(),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigné à')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('Message')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description du problème')
                            ->rows(5)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Notes de résolution')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
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
                Tables\Columns\TextColumn::make('subject')
                    ->label('Sujet')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'booking' => 'Réservation',
                        'payment' => 'Paiement',
                        'account' => 'Compte',
                        'residence' => 'Résidence',
                        'technical' => 'Technique',
                        'other' => 'Autre',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorité')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'low' => 'Basse',
                        'medium' => 'Moyenne',
                        'high' => 'Haute',
                        'urgent' => 'Urgente',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'open' => 'danger',
                        'in_progress' => 'warning',
                        'waiting' => 'info',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'open' => 'Ouvert',
                        'in_progress' => 'En cours',
                        'waiting' => 'En attente',
                        'resolved' => 'Résolu',
                        'closed' => 'Fermé',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigné à')
                    ->placeholder('Non assigné'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'open' => 'Ouvert',
                        'in_progress' => 'En cours',
                        'waiting' => 'En attente',
                        'resolved' => 'Résolu',
                        'closed' => 'Fermé',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorité')
                    ->options([
                        'low' => 'Basse',
                        'medium' => 'Moyenne',
                        'high' => 'Haute',
                        'urgent' => 'Urgente',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'booking' => 'Réservation',
                        'payment' => 'Paiement',
                        'account' => 'Compte',
                        'residence' => 'Résidence',
                        'technical' => 'Technique',
                        'other' => 'Autre',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => !in_array($record->status, ['resolved', 'closed']))
                    ->action(fn ($record) => $record->update(['status' => 'resolved'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'assignedTo']);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'create' => Pages\CreateSupportTicket::route('/create'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['open', 'in_progress'])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
