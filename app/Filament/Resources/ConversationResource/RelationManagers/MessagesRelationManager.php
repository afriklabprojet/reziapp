<?php

namespace App\Filament\Resources\ConversationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Messages';

    protected static ?string $modelLabel = 'Message';

    protected static ?string $pluralModelLabel = 'Messages';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Expéditeur')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('content')
                    ->label('Message')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'gray',
                        'image' => 'info',
                        'file', 'document' => 'warning',
                        'system' => 'primary',
                        'auto_reply' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('read_at')
                    ->label('Lu')
                    ->dateTime('d/m H:i')
                    ->placeholder('Non lu')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Envoyé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer')
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer ce message')
                    ->modalDescription('Ce message sera supprimé définitivement. Cette action est irréversible.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer les messages sélectionnés'),
                ]),
            ])
            ->heading('Historique des messages')
            ->description('Tous les messages de cette conversation (modération)')
            ->emptyStateHeading('Aucun message')
            ->emptyStateDescription('Cette conversation ne contient aucun message.');
    }

    public function isReadOnly(): bool
    {
        return false; // Allow delete actions for moderation
    }
}
