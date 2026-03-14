<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Models\Campaign;
use App\Services\MarketingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Campagnes';

    protected static ?string $modelLabel = 'Campagne';

    protected static ?string $pluralModelLabel = 'Campagnes';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Campaign')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informations')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom de la campagne')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ex: Newsletter Janvier 2025'),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(2)
                                    ->placeholder('Description interne de la campagne'),
                                Forms\Components\Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        'email' => 'Email',
                                        'sms' => 'SMS',
                                        'push' => 'Notification Push',
                                        'in_app' => 'Message In-App',
                                    ])
                                    ->required()
                                    ->default('email')
                                    ->live(),
                                Forms\Components\Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'draft' => 'Brouillon',
                                        'scheduled' => 'Planifiée',
                                        'sending' => 'En cours d\'envoi',
                                        'sent' => 'Envoyée',
                                        'failed' => 'Échouée',
                                    ])
                                    ->default('draft')
                                    ->disabled(),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Contenu')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\TextInput::make('subject')
                                    ->label('Sujet')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Sujet de l\'email ou titre du message')
                                    ->helperText('Variables: {{name}}, {{email}}, {{first_name}}'),
                                Forms\Components\Select::make('template')
                                    ->label('Template')
                                    ->options([
                                        'default' => 'Par défaut',
                                        'newsletter' => 'Newsletter',
                                        'promotional' => 'Promotionnel',
                                        'transactional' => 'Transactionnel',
                                    ])
                                    ->default('default'),
                                Forms\Components\RichEditor::make('content')
                                    ->label('Contenu')
                                    ->required()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'strike',
                                        'link',
                                        'orderedList',
                                        'bulletList',
                                        'h2',
                                        'h3',
                                        'blockquote',
                                        'codeBlock',
                                    ])
                                    ->columnSpanFull()
                                    ->helperText('Variables disponibles: {{name}}, {{email}}, {{first_name}}, {{unsubscribe_link}}'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Audience')
                            ->icon('heroicon-o-users')
                            ->schema([
                                Forms\Components\Select::make('audience')
                                    ->label('Audience cible')
                                    ->options([
                                        'all' => 'Tous les utilisateurs',
                                        'owners' => 'Propriétaires uniquement',
                                        'tenants' => 'Locataires uniquement',
                                        'active' => 'Utilisateurs actifs (30 jours)',
                                        'inactive' => 'Utilisateurs inactifs (+30 jours)',
                                        'new' => 'Nouveaux utilisateurs (7 jours)',
                                        'verified' => 'Utilisateurs vérifiés',
                                        'custom' => 'Personnalisé',
                                    ])
                                    ->default('all')
                                    ->required()
                                    ->live(),
                                Forms\Components\KeyValue::make('audience_filters')
                                    ->label('Filtres personnalisés')
                                    ->keyLabel('Critère')
                                    ->valueLabel('Valeur')
                                    ->visible(fn ($get) => $get('audience') === 'custom')
                                    ->helperText('Ex: commune => cocody, role => owner'),
                                Forms\Components\Select::make('excluded_user_ids')
                                    ->label('Utilisateurs exclus')
                                    ->multiple()
                                    ->options(fn () => \App\Models\User::limit(200)->pluck('name', 'id'))
                                    ->searchable()
                                    ->helperText('Sélectionnez les utilisateurs à exclure'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Planification')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Forms\Components\DateTimePicker::make('scheduled_at')
                                    ->label('Date d\'envoi planifiée')
                                    ->helperText('Laisser vide pour un envoi manuel'),
                                Forms\Components\Toggle::make('track_opens')
                                    ->label('Suivre les ouvertures')
                                    ->default(true)
                                    ->helperText('Ajouter un pixel de tracking'),
                                Forms\Components\Toggle::make('track_clicks')
                                    ->label('Suivre les clics')
                                    ->default(true)
                                    ->helperText('Tracker les liens dans le contenu'),
                            ])->columns(1),

                        Forms\Components\Tabs\Tab::make('Statistiques')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Placeholder::make('recipients_count_display')
                                    ->label('Destinataires')
                                    ->content(fn ($record) => $record?->recipients_count ?? 0),
                                Forms\Components\Placeholder::make('delivered_count_display')
                                    ->label('Délivrés')
                                    ->content(fn ($record) => $record?->delivered_count ?? 0),
                                Forms\Components\Placeholder::make('opened_count_display')
                                    ->label('Ouverts')
                                    ->content(fn ($record) => $record?->opened_count ?? 0),
                                Forms\Components\Placeholder::make('clicked_count_display')
                                    ->label('Cliqués')
                                    ->content(fn ($record) => $record?->clicked_count ?? 0),
                                Forms\Components\Placeholder::make('bounced_count_display')
                                    ->label('Rebonds')
                                    ->content(fn ($record) => $record?->bounced_count ?? 0),
                                Forms\Components\Placeholder::make('open_rate_display')
                                    ->label('Taux d\'ouverture')
                                    ->content(fn ($record) => $record && $record->recipients_count > 0
                                        ? round(($record->opened_count / $record->recipients_count) * 100, 1).'%'
                                        : '0%'),
                            ])->columns(3)
                            ->visible(fn ($record) => $record !== null),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'email' => 'primary',
                        'sms' => 'success',
                        'push' => 'warning',
                        'in_app' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match($state) {
                        'email' => 'heroicon-o-envelope',
                        'sms' => 'heroicon-o-device-phone-mobile',
                        'push' => 'heroicon-o-bell',
                        'in_app' => 'heroicon-o-chat-bubble-left',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'push' => 'Push',
                        'in_app' => 'In-App',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('audience')
                    ->label('Audience')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'all' => 'Tous',
                        'owners' => 'Propriétaires',
                        'tenants' => 'Locataires',
                        'active' => 'Actifs',
                        'inactive' => 'Inactifs',
                        'new' => 'Nouveaux',
                        'verified' => 'Vérifiés',
                        'custom' => 'Personnalisé',
                        default => $state ?? 'Tous',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'draft' => 'gray',
                        'scheduled' => 'warning',
                        'sending' => 'info',
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match($state) {
                        'draft' => 'heroicon-o-pencil',
                        'scheduled' => 'heroicon-o-clock',
                        'sending' => 'heroicon-o-arrow-path',
                        'sent' => 'heroicon-o-check-circle',
                        'failed' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'draft' => 'Brouillon',
                        'scheduled' => 'Planifiée',
                        'sending' => 'En cours',
                        'sent' => 'Envoyée',
                        'failed' => 'Échouée',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('recipients_count')
                    ->label('Dest.')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—')
                    ->description(fn ($record) => $record->delivered_count ? $record->delivered_count.' délivrés' : null),
                Tables\Columns\TextColumn::make('opened_count')
                    ->label('Ouverts')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn ($record) => $record->opened_count > 0 ? 'success' : null),
                Tables\Columns\TextColumn::make('clicked_count')
                    ->label('Clics')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Planifiée')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Envoyée')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'push' => 'Push',
                        'in_app' => 'In-App',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'scheduled' => 'Planifiée',
                        'sending' => 'En cours',
                        'sent' => 'Envoyée',
                        'failed' => 'Échouée',
                    ]),
                Tables\Filters\SelectFilter::make('audience')
                    ->label('Audience')
                    ->options([
                        'all' => 'Tous',
                        'owners' => 'Propriétaires',
                        'tenants' => 'Locataires',
                        'active' => 'Actifs',
                        'inactive' => 'Inactifs',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('duplicate')
                    ->label('Dupliquer')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Campaign $record) {
                        $new = $record->replicate();
                        $new->name = $record->name.' (copie)';
                        $new->status = 'draft';
                        $new->scheduled_at = null;
                        $new->sent_at = null;
                        $new->recipients_count = 0;
                        $new->delivered_count = 0;
                        $new->opened_count = 0;
                        $new->clicked_count = 0;
                        $new->bounced_count = 0;
                        $new->unsubscribed_count = 0;
                        $new->save();

                        Notification::make()
                            ->title('Campagne dupliquée')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('send')
                    ->label('Envoyer')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'scheduled']))
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer la campagne')
                    ->modalDescription(fn (Campaign $record) => 'Êtes-vous sûr de vouloir envoyer cette campagne à '
                        . number_format(app(MarketingService::class)->getCampaignRecipients($record)->count(), 0, ',', ' ')
                        . ' destinataires ? Cette action est irréversible.')
                    ->modalSubmitActionLabel('Oui, envoyer')
                    ->action(function (Campaign $record) {
                        try {
                            $result = app(MarketingService::class)->sendCampaign($record);

                            if ($result['success']) {
                                Notification::make()
                                    ->title('Campagne envoyée !')
                                    ->body("{$result['sent']} messages envoyés, {$result['failed']} échecs.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Erreur d\'envoi')
                                    ->body($result['error'] ?? 'Une erreur est survenue.')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur d\'envoi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('test')
                    ->label('Tester')
                    ->icon('heroicon-o-beaker')
                    ->color('info')
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'scheduled']))
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer un test')
                    ->modalDescription('Un message de test sera envoyé à votre adresse email.')
                    ->modalSubmitActionLabel('Envoyer le test')
                    ->action(function (Campaign $record) {
                        try {
                            $user = auth()->user();
                            $content = self::personalizeContent($record->content, $user);

                            if ($record->type === 'email') {
                                Mail::raw($content, function ($message) use ($record, $user) {
                                    $message->to($user->email)
                                        ->subject('[TEST] ' . ($record->subject ?? $record->name));
                                });
                            }

                            Notification::make()
                                ->title('Test envoyé !')
                                ->body('Message de test envoyé à ' . $user->email)
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\DeleteAction::make()->label(''),
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
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $draft = static::getModel()::where('status', 'draft')->count();
        $scheduled = static::getModel()::where('status', 'scheduled')->count();
        $total = $draft + $scheduled;

        return $total ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $scheduled = static::getModel()::where('status', 'scheduled')->count();

        return $scheduled > 0 ? 'warning' : 'gray';
    }

    /**
     * Personnaliser le contenu avec les données utilisateur.
     */
    public static function personalizeContent(string $content, $user): string
    {
        $replacements = [
            '{{name}}' => $user->name,
            '{{first_name}}' => explode(' ', $user->name)[0],
            '{{email}}' => $user->email,
            '{{phone}}' => $user->phone ?? '',
            '{{referral_code}}' => $user->referral_code ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
