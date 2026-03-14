<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages;
use App\Models\Message;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationGroup = 'Modération';

    protected static ?string $navigationLabel = 'Messages';

    protected static ?string $modelLabel = 'Message';

    protected static ?string $pluralModelLabel = 'Messages';

    protected static ?int $navigationSort = 3;

    // Note: Accès limité pour modération de contenu inapproprié

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Message')
                    ->schema([
                        Forms\Components\Select::make('conversation_id')
                            ->label('Conversation')
                            ->relationship('conversation', 'id')
                            ->required(),
                        Forms\Components\Select::make('sender_id')
                            ->label('Expéditeur')
                            ->relationship('sender', 'name')
                            ->required(),
                        Forms\Components\Textarea::make('content')
                            ->label('Contenu')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'text' => 'Texte',
                                'image' => 'Image',
                                'file' => 'Fichier',
                                'system' => 'Système',
                            ])
                            ->default('text'),
                        Forms\Components\DateTimePicker::make('read_at')
                            ->label('Lu le'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('conversation_id')
                    ->label('Conv. #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Expéditeur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('content')
                    ->label('Message')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'text' => 'gray',
                        'image' => 'info',
                        'file' => 'warning',
                        'system' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('read_at')
                    ->label('Lu')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !is_null($record->read_at)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Envoyé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'text' => 'Texte',
                        'image' => 'Image',
                        'file' => 'Fichier',
                        'system' => 'Système',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
            'create' => Pages\CreateMessage::route('/create'),
            'edit' => Pages\EditMessage::route('/{record}/edit'),
        ];
    }
}
