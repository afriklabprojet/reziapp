<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConversationResource\Pages;
use App\Filament\Resources\ConversationResource\RelationManagers;
use App\Models\Conversation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Modération';

    protected static ?string $navigationLabel = 'Conversations';

    protected static ?string $modelLabel = 'Conversation';

    protected static ?string $pluralModelLabel = 'Conversations';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Modération')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'active' => 'Active',
                                'archived' => 'Archivée',
                                'blocked' => 'Bloquée',
                            ])
                            ->required(),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Participants')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Chercheur'),
                        Infolists\Components\TextEntry::make('owner.name')
                            ->label('Propriétaire'),
                        Infolists\Components\TextEntry::make('residence.name')
                            ->label('Résidence'),
                    ])->columns(3),

                Infolists\Components\Section::make('Détails')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'archived' => 'gray',
                                'blocked' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('messages_count')
                            ->label('Messages')
                            ->state(fn (Conversation $record): int => $record->messages()->count()),
                        Infolists\Components\TextEntry::make('last_message_at')
                            ->label('Dernier message')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Créée le')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Chercheur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('residence.name')
                    ->label('Résidence')
                    ->limit(20)
                    ->sortable(),
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Messages')
                    ->counts('messages')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'archived' => 'gray',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('last_message_at')
                    ->label('Dernier message')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actives',
                        'archived' => 'Archivées',
                        'blocked' => 'Bloquées',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\Action::make('block')
                    ->label('Bloquer')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Conversation $record) => $record->block())
                    ->visible(fn (Conversation $record): bool => $record->status !== 'blocked'),
                Tables\Actions\Action::make('unblock')
                    ->label('Débloquer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Conversation $record) => $record->unblock())
                    ->visible(fn (Conversation $record): bool => $record->status === 'blocked'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversations::route('/'),
            'view' => Pages\ViewConversation::route('/{record}'),
            'edit' => Pages\EditConversation::route('/{record}/edit'),
        ];
    }
}
