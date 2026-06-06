<?php

namespace App\Filament\Resources\CampaignResource\Support;

use App\Models\Campaign;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;

class CampaignResourceUi
{
    private const TYPE_OPTIONS = [
        'email' => 'Email',
        'sms' => 'SMS',
        'push' => 'Notification Push',
        'in_app' => 'Message In-App',
    ];

    private const TYPE_TABLE_LABELS = [
        'email' => 'Email',
        'sms' => 'SMS',
        'push' => 'Push',
        'in_app' => 'In-App',
    ];

    private const TYPE_COLORS = [
        'email' => 'primary',
        'sms' => 'success',
        'push' => 'warning',
        'in_app' => 'info',
    ];

    private const TYPE_ICONS = [
        'email' => 'heroicon-o-envelope',
        'sms' => 'heroicon-o-device-phone-mobile',
        'push' => 'heroicon-o-bell',
        'in_app' => 'heroicon-o-chat-bubble-left',
    ];

    private const TEMPLATE_OPTIONS = [
        'default' => 'Par défaut',
        'newsletter' => 'Newsletter',
        'promotional' => 'Promotionnel',
        'transactional' => 'Transactionnel',
    ];

    private const AUDIENCE_OPTIONS = [
        'all' => 'Tous les utilisateurs',
        'owners' => 'Propriétaires uniquement',
        'tenants' => 'Locataires uniquement',
        'active' => 'Utilisateurs actifs (30 jours)',
        'inactive' => 'Utilisateurs inactifs (+30 jours)',
        'new' => 'Nouveaux utilisateurs (7 jours)',
        'verified' => 'Utilisateurs vérifiés',
        'custom' => 'Personnalisé',
    ];

    private const AUDIENCE_BADGE_LABELS = [
        'all' => 'Tous',
        'owners' => 'Propriétaires',
        'tenants' => 'Locataires',
        'active' => 'Actifs',
        'inactive' => 'Inactifs',
        'new' => 'Nouveaux',
        'verified' => 'Vérifiés',
        'custom' => 'Personnalisé',
    ];

    private const STATUS_FORM_LABELS = [
        'draft' => 'Brouillon',
        'scheduled' => 'Planifiée',
        'sending' => 'En cours d\'envoi',
        'sent' => 'Envoyée',
        'failed' => 'Échouée',
    ];

    private const STATUS_TABLE_LABELS = [
        'draft' => 'Brouillon',
        'scheduled' => 'Planifiée',
        'sending' => 'En cours',
        'sent' => 'Envoyée',
        'failed' => 'Échouée',
    ];

    private const STATUS_COLORS = [
        'draft' => 'gray',
        'scheduled' => 'warning',
        'sending' => 'info',
        'sent' => 'success',
        'failed' => 'danger',
    ];

    private const STATUS_ICONS = [
        'draft' => 'heroicon-o-pencil',
        'scheduled' => 'heroicon-o-clock',
        'sending' => 'heroicon-o-arrow-path',
        'sent' => 'heroicon-o-check-circle',
        'failed' => 'heroicon-o-x-circle',
    ];

    public static function formTabs(): array
    {
        return [
            self::makeInformationTab(),
            self::makeContentTab(),
            self::makeAudienceTab(),
            self::makeSchedulingTab(),
            self::makeStatisticsTab(),
        ];
    }

    public static function tableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Nom')
                ->searchable()
                ->sortable()
                ->limit(40),
            Tables\Columns\TextColumn::make('type')
                ->label('Type')
                ->badge()
                ->color(fn (?string $state): string => self::TYPE_COLORS[$state] ?? 'gray')
                ->icon(fn (?string $state): string => self::TYPE_ICONS[$state] ?? 'heroicon-o-question-mark-circle')
                ->formatStateUsing(fn (?string $state): string => self::TYPE_TABLE_LABELS[$state] ?? (string) $state),
            Tables\Columns\TextColumn::make('audience')
                ->label('Audience')
                ->badge()
                ->color('gray')
                ->formatStateUsing(fn (?string $state): string => self::AUDIENCE_BADGE_LABELS[$state] ?? $state ?? 'Tous'),
            Tables\Columns\TextColumn::make('status')
                ->label('Statut')
                ->badge()
                ->color(fn (?string $state): string => self::STATUS_COLORS[$state] ?? 'gray')
                ->icon(fn (?string $state): string => self::STATUS_ICONS[$state] ?? 'heroicon-o-question-mark-circle')
                ->formatStateUsing(fn (?string $state): string => self::STATUS_TABLE_LABELS[$state] ?? (string) $state),
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
                ->label(self::STATUS_FORM_LABELS['scheduled'])
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('sent_at')
                ->label(self::STATUS_FORM_LABELS['sent'])
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->placeholder('—'),
        ];
    }

    public static function tableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('type')
                ->label('Type')
                ->options(self::TYPE_TABLE_LABELS),
            Tables\Filters\SelectFilter::make('status')
                ->label('Statut')
                ->options(self::STATUS_TABLE_LABELS),
            Tables\Filters\SelectFilter::make('audience')
                ->label('Audience')
                ->options([
                    'all' => 'Tous',
                    'owners' => 'Propriétaires',
                    'tenants' => 'Locataires',
                    'active' => 'Actifs',
                    'inactive' => 'Inactifs',
                ]),
        ];
    }

    public static function tableActions(): array
    {
        return [
            self::makeDuplicateAction(),
            CampaignActionFactory::makeTableSendAction(),
            CampaignActionFactory::makeTableTestAction(),
            Tables\Actions\EditAction::make()->label(''),
            Tables\Actions\DeleteAction::make()->label(''),
        ];
    }

    private static function makeInformationTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Informations')
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
                    ->options(self::TYPE_OPTIONS)
                    ->required()
                    ->default('email')
                    ->live(),
                Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options(self::STATUS_FORM_LABELS)
                    ->default('draft')
                    ->disabled(),
            ])
            ->columns(2);
    }

    private static function makeContentTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Contenu')
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
                    ->options(self::TEMPLATE_OPTIONS)
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
            ]);
    }

    private static function makeAudienceTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Audience')
            ->icon('heroicon-o-users')
            ->schema([
                Forms\Components\Select::make('audience')
                    ->label('Audience cible')
                    ->options(self::AUDIENCE_OPTIONS)
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
                    ->options(fn () => User::limit(200)->pluck('name', 'id'))
                    ->searchable()
                    ->helperText('Sélectionnez les utilisateurs à exclure'),
            ]);
    }

    private static function makeSchedulingTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Planification')
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
            ])
            ->columns(1);
    }

    private static function makeStatisticsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Statistiques')
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
            ])
            ->columns(3)
            ->visible(fn ($record) => $record !== null);
    }

    private static function makeDuplicateAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('duplicate')
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
            });
    }

}
