<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookEventResource\Pages;
use App\Models\WebhookEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WebhookEventResource extends Resource
{
    protected static ?string $model = WebhookEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Développeurs';

    protected static ?string $navigationLabel = 'Événements Webhook';

    protected static ?string $modelLabel = 'Événement Webhook';

    protected static ?string $pluralModelLabel = 'Événements Webhook';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Webhook')
                    ->schema([
                        Forms\Components\TextInput::make('provider')
                            ->label('Fournisseur')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('event_id')
                            ->label('ID événement')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('event_type')
                            ->label('Type')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('status')
                            ->label('Statut')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\KeyValue::make('payload')
                            ->label('Payload')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->label('Fournisseur')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('event_type')
                    ->badge()
                    ->label('Type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event_id')
                    ->badge()
                    ->label('ID événement')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'processed',
                        'danger'  => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->badge()
                    ->label('Reçu le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->label('Fournisseur')
                    ->options(fn () => WebhookEvent::distinct()->pluck('provider', 'provider')->toArray()),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending'   => 'En attente',
                        'processed' => 'Traité',
                        'failed'    => 'Échec',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookEvents::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
