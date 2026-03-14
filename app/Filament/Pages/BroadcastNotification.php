<?php

namespace App\Filament\Pages;

use App\Models\AdminActivityLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BroadcastNotification extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Envoyer notification';

    protected static ?string $title = 'Envoyer une notification groupée';

    protected static ?int $navigationSort = 12;

    protected static string $view = 'filament.pages.broadcast-notification';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'target_audience' => 'all',
            'channel' => ['database'],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Destinataires')
                    ->description('Sélectionnez qui recevra cette notification')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\Select::make('target_audience')
                            ->label('Audience cible')
                            ->options([
                                'all' => 'Tous les utilisateurs',
                                'owners' => 'Propriétaires uniquement',
                                'users' => 'Locataires uniquement',
                                'verified_owners' => 'Propriétaires vérifiés',
                                'active_users' => 'Utilisateurs actifs (30 jours)',
                                'specific' => 'Utilisateurs spécifiques',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $state !== 'specific' ? $set('specific_users', []) : null),

                        Forms\Components\Select::make('specific_users')
                            ->label('Utilisateurs spécifiques')
                            ->multiple()
                            ->searchable()
                            ->options(fn () => User::where('role', '!=', 'admin')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->visible(fn (Forms\Get $get) => $get('target_audience') === 'specific')
                            ->required(fn (Forms\Get $get) => $get('target_audience') === 'specific'),

                        Forms\Components\Placeholder::make('count')
                            ->label('Nombre de destinataires')
                            ->content(function (Forms\Get $get) {
                                $audience = $get('target_audience');
                                $specific = $get('specific_users') ?? [];

                                return match ($audience) {
                                    'all' => User::where('role', '!=', 'admin')->count() . ' utilisateurs',
                                    'owners' => User::where('role', 'owner')->count() . ' propriétaires',
                                    'users' => User::where('role', 'user')->count() . ' locataires',
                                    'verified_owners' => User::where('role', 'owner')->where('is_verified', true)->count() . ' propriétaires vérifiés',
                                    'active_users' => User::where('role', '!=', 'admin')->where('updated_at', '>=', now()->subDays(30))->count() . ' utilisateurs actifs',
                                    'specific' => count($specific) . ' utilisateurs sélectionnés',
                                    default => '-',
                                };
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Contenu de la notification')
                    ->description('Rédigez votre message')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Ex: Nouvelle fonctionnalité disponible')
                            ->helperText('Maximum 100 caractères'),

                        Forms\Components\Textarea::make('body')
                            ->label('Message')
                            ->required()
                            ->maxLength(500)
                            ->rows(4)
                            ->placeholder('Ex: Découvrez notre nouvelle fonctionnalité de réservation instantanée...')
                            ->helperText('Maximum 500 caractères'),

                        Forms\Components\TextInput::make('action_url')
                            ->label('Lien d\'action (optionnel)')
                            ->url()
                            ->placeholder('https://reziapp.ci/...')
                            ->helperText('URL vers laquelle l\'utilisateur sera redirigé'),

                        Forms\Components\Select::make('icon')
                            ->label('Icône')
                            ->options([
                                'info' => 'ℹ️ Information',
                                'success' => '✅ Succès',
                                'warning' => '⚠️ Attention',
                                'promo' => '🎉 Promotion',
                                'update' => '🔄 Mise à jour',
                                'announcement' => '📢 Annonce',
                            ])
                            ->default('info'),
                    ]),

                Forms\Components\Section::make('Canaux de diffusion')
                    ->description('Choisissez comment envoyer la notification')
                    ->icon('heroicon-o-signal')
                    ->schema([
                        Forms\Components\CheckboxList::make('channel')
                            ->label('Canaux')
                            ->options([
                                'database' => '🔔 Notification in-app',
                                'email' => '📧 Email',
                                // 'sms' => '📱 SMS',
                            ])
                            ->required()
                            ->columns(2),

                        Forms\Components\Toggle::make('schedule')
                            ->label('Programmer l\'envoi')
                            ->live(),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Date et heure d\'envoi')
                            ->visible(fn (Forms\Get $get) => $get('schedule'))
                            ->required(fn (Forms\Get $get) => $get('schedule'))
                            ->minDate(now())
                            ->native(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        // Get target users
        $usersQuery = User::where('role', '!=', 'admin');

        switch ($data['target_audience']) {
            case 'owners':
                $usersQuery->where('role', 'owner');
                break;
            case 'users':
                $usersQuery->where('role', 'user');
                break;
            case 'verified_owners':
                $usersQuery->where('role', 'owner')->where('is_verified', true);
                break;
            case 'active_users':
                $usersQuery->where('updated_at', '>=', now()->subDays(30));
                break;
            case 'specific':
                $usersQuery->whereIn('id', $data['specific_users'] ?? []);
                break;
        }

        $users = $usersQuery->get();

        if ($users->isEmpty()) {
            Notification::make()
                ->title('Erreur')
                ->body('Aucun utilisateur correspondant aux critères')
                ->danger()
                ->send();
            return;
        }

        // Send notifications
        $notification = new \App\Notifications\BroadcastNotification(
            title: $data['title'],
            body: $data['body'],
            actionUrl: $data['action_url'] ?? null,
            icon: $data['icon'] ?? 'info'
        );

        $channels = $data['channel'];

        DB::beginTransaction();
        try {
            foreach ($users as $user) {
                if (in_array('database', $channels)) {
                    $user->notify($notification);
                }

                if (in_array('email', $channels) && $user->email) {
                    // Queue email pour éviter timeout
                    \App\Jobs\SendBroadcastEmail::dispatch($user, $data['title'], $data['body'], $data['action_url'] ?? null);
                }
            }

            // Log activity
            AdminActivityLog::log(
                AdminActivityLog::ACTION_NOTIFICATION_SENT,
                "Notification envoyée à {$users->count()} utilisateurs: {$data['title']}",
                null,
                null,
                [
                    'audience' => $data['target_audience'],
                    'channels' => $channels,
                    'recipient_count' => $users->count(),
                ]
            );

            DB::commit();

            Notification::make()
                ->title('Notification envoyée')
                ->body("La notification a été envoyée à {$users->count()} utilisateurs")
                ->success()
                ->send();

            // Reset form
            $this->form->fill([
                'target_audience' => 'all',
                'channel' => ['database'],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Erreur')
                ->body('Erreur lors de l\'envoi: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('send')
                ->label('Envoyer la notification')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->size('lg')
                ->action('send')
                ->requiresConfirmation()
                ->modalHeading('Confirmer l\'envoi')
                ->modalDescription('Êtes-vous sûr de vouloir envoyer cette notification ? Cette action est irréversible.')
                ->modalSubmitActionLabel('Oui, envoyer'),
        ];
    }
}
