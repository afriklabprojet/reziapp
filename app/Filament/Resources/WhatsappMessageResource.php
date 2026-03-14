<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsappMessageResource\Pages;
use App\Models\WhatsappMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WhatsappMessageResource extends Resource
{
    protected static ?string $model = WhatsappMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Messages WhatsApp';

    protected static ?string $modelLabel = 'Message WhatsApp';

    protected static ?string $pluralModelLabel = 'Messages WhatsApp';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du message')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('phone_number')
                            ->label('Numéro de téléphone')
                            ->required()
                            ->tel(),

                        Forms\Components\Select::make('direction')
                            ->label('Direction')
                            ->options([
                                'outbound' => 'Sortant',
                                'inbound' => 'Entrant',
                            ])
                            ->required(),

                        Forms\Components\Select::make('message_type')
                            ->label('Type de message')
                            ->options([
                                'text' => 'Texte',
                                'template' => 'Template',
                                'image' => 'Image',
                                'document' => 'Document',
                                'location' => 'Localisation',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Contenu')
                    ->schema([
                        Forms\Components\TextInput::make('template_name')
                            ->label('Nom du template')
                            ->visible(fn (Forms\Get $get) => $get('message_type') === 'template'),

                        Forms\Components\Textarea::make('content')
                            ->label('Contenu')
                            ->rows(4)
                            ->required(),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Métadonnées')
                            ->keyLabel('Clé')
                            ->valueLabel('Valeur'),
                    ]),

                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'sent' => 'Envoyé',
                                'delivered' => 'Délivré',
                                'read' => 'Lu',
                                'failed' => 'Échoué',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('error_message')
                            ->label('Message d\'erreur')
                            ->rows(2)
                            ->visible(fn (Forms\Get $get) => $get('status') === 'failed'),

                        Forms\Components\TextInput::make('whatsapp_message_id')
                            ->label('ID WhatsApp')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Numéro')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\IconColumn::make('direction')
                    ->label('Direction')
                    ->icon(fn (string $state): string => match ($state) {
                        'outbound' => 'heroicon-o-arrow-up-right',
                        'inbound' => 'heroicon-o-arrow-down-left',
                        default => 'heroicon-o-minus',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'outbound' => 'success',
                        'inbound' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('message_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'text' => 'Texte',
                        'template' => 'Template',
                        'image' => 'Image',
                        'document' => 'Doc',
                        'location' => 'Lieu',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('content')
                    ->label('Contenu')
                    ->limit(50)
                    ->tooltip(fn (WhatsappMessage $record): string => $record->content ?? ''),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'sent',
                        'success' => fn ($state) => in_array($state, ['delivered', 'read']),
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'En attente',
                        'sent' => 'Envoyé',
                        'delivered' => 'Délivré',
                        'read' => 'Lu',
                        'failed' => 'Échoué',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('direction')
                    ->label('Direction')
                    ->options([
                        'outbound' => 'Sortant',
                        'inbound' => 'Entrant',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'sent' => 'Envoyé',
                        'delivered' => 'Délivré',
                        'read' => 'Lu',
                        'failed' => 'Échoué',
                    ]),

                Tables\Filters\SelectFilter::make('message_type')
                    ->label('Type')
                    ->options([
                        'text' => 'Texte',
                        'template' => 'Template',
                        'image' => 'Image',
                        'document' => 'Document',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('resend')
                    ->label('Renvoyer')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (WhatsappMessage $record) => $record->status === 'failed' && $record->direction === 'outbound'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListWhatsappMessages::route('/'),
            'view' => Pages\ViewWhatsappMessage::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'failed')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
