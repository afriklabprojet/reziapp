<?php

namespace App\Filament\Pages;

use App\Models\PlatformSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PlatformSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Commissions & paiements';

    protected static ?string $title = 'Commissions et paiements';

    protected static ?string $slug = 'commissions-paiements';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.platform-settings';

    public ?array $commissionData = [];
    public ?array $paymentData = [];
    public ?array $bookingData = [];
    public ?array $pricingData = [];
    public ?array $generalData = [];
    public ?array $defaultsData = [];

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $settings = PlatformSetting::all()->keyBy('key');

        $this->commissionData = [
            'commission_rate' => $settings->get('commission_rate')?->value ?? 10,
            'commission_min' => $settings->get('commission_min')?->value ?? 1000,
            'owner_payout_delay' => $settings->get('owner_payout_delay')?->value ?? 48,
        ];

        $this->paymentData = [
            'min_booking_amount' => $settings->get('min_booking_amount')?->value ?? 5000,
            'max_booking_amount' => $settings->get('max_booking_amount')?->value ?? 10000000,
            'payment_methods' => json_decode($settings->get('payment_methods')?->value ?? '[]', true),
        ];

        $this->bookingData = [
            'min_booking_days' => $settings->get('min_booking_days')?->value ?? 1,
            'max_booking_days' => $settings->get('max_booking_days')?->value ?? 365,
            'advance_booking_days' => $settings->get('advance_booking_days')?->value ?? 180,
        ];

        $this->pricingData = [
            'state_tax' => $settings->get('state_tax')?->value ?? config('rezi.pricing.state_tax', 1000),
        ];

        $this->generalData = [
            'platform_name' => $settings->get('platform_name')?->value ?? 'Rezi App',
            'platform_email' => $settings->get('platform_email')?->value ?? '',
            'platform_phone' => $settings->get('platform_phone')?->value ?? '',
            'maintenance_mode' => (bool) ($settings->get('maintenance_mode')?->value ?? false),
        ];

        $this->defaultsData = [
            'default_check_in_time'  => $settings->get('default_check_in_time')?->value ?? config('rezi.defaults.check_in_time', '14h00'),
            'default_check_out_time' => $settings->get('default_check_out_time')?->value ?? config('rezi.defaults.check_out_time', '12h00'),
            'default_min_nights'     => (int) ($settings->get('default_min_nights')?->value ?? config('rezi.defaults.min_nights', 1)),
            'default_max_nights'     => (int) ($settings->get('default_max_nights')?->value ?? config('rezi.defaults.max_nights', 365)),
            'default_max_guests'     => (int) ($settings->get('default_max_guests')?->value ?? config('rezi.defaults.max_guests', 4)),
            'default_city'           => $settings->get('default_city')?->value ?? config('rezi.defaults.city', 'Abidjan'),
        ];
    }

    protected function getForms(): array
    {
        return [
            'commissionForm',
            'paymentForm',
            'bookingForm',
            'pricingForm',
            'generalForm',
            'defaultsForm',
        ];
    }

    public function commissionForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Commissions')
                    ->description('Paramètres de commission appliqués au propriétaire sur chaque réservation')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Taux de commission propriétaire (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(50)
                            ->suffix('%')
                            ->required()
                            ->helperText('Exemple : 10 pour prélever 10% sur le montant total de chaque réservation'),
                        Forms\Components\TextInput::make('commission_min')
                            ->label('Commission minimum par réservation')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->required()
                            ->helperText('Montant minimum de commission par réservation'),
                        Forms\Components\TextInput::make('owner_payout_delay')
                            ->label('Délai de versement')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('heures')
                            ->required()
                            ->helperText('Délai après le check-in pour verser aux propriétaires'),
                    ])->columns(3),
            ])
            ->statePath('commissionData');
    }

    public function paymentForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Paiements')
                    ->description('Configuration des paiements')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\TextInput::make('min_booking_amount')
                            ->label('Montant minimum')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->required(),
                        Forms\Components\TextInput::make('max_booking_amount')
                            ->label('Montant maximum')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->required(),
                        Forms\Components\CheckboxList::make('payment_methods')
                            ->label('Moyens de paiement activés')
                            ->options([
                                'orange_money' => 'Orange Money',
                                'mtn_money' => 'MTN Money',
                                'wave' => 'Wave',
                                'moov_money' => 'Moov Money',
                                'card' => 'Carte bancaire',
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('paymentData');
    }

    public function bookingForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Réservations')
                    ->description('Règles de réservation')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\TextInput::make('min_booking_days')
                            ->label('Durée minimum')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('jours')
                            ->required(),
                        Forms\Components\TextInput::make('max_booking_days')
                            ->label('Durée maximum')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('jours')
                            ->required(),
                        Forms\Components\TextInput::make('advance_booking_days')
                            ->label('Réservation à l\'avance')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('jours max')
                            ->required()
                            ->helperText('Combien de jours à l\'avance peut-on réserver'),
                    ])->columns(3),
            ])
            ->statePath('bookingData');
    }

    public function generalForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Général')
                    ->description('Paramètres généraux de la plateforme')
                    ->icon('heroicon-o-cog')
                    ->schema([
                        Forms\Components\TextInput::make('platform_name')
                            ->label('Nom de la plateforme')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('platform_email')
                            ->label('Email de contact')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('platform_phone')
                            ->label('Téléphone')
                            ->tel(),
                        Forms\Components\Toggle::make('maintenance_mode')
                            ->label('Mode maintenance')
                            ->helperText('Activer pour bloquer l\'accès au site')
                            ->onColor('danger')
                            ->offColor('success'),
                    ])->columns(2),
            ])
            ->statePath('generalData');
    }

    public function saveCommission(): void
    {
        $data = $this->commissionForm->getState();

        foreach ($data as $key => $value) {
            PlatformSetting::setValue($key, $value);
        }

        Notification::make()
            ->title('Paramètres de commission enregistrés')
            ->success()
            ->send();
    }

    public function savePayment(): void
    {
        $data = $this->paymentForm->getState();

        foreach ($data as $key => $value) {
            PlatformSetting::setValue($key, $value);
        }

        Notification::make()
            ->title('Paramètres de paiement enregistrés')
            ->success()
            ->send();
    }

    public function saveBooking(): void
    {
        $data = $this->bookingForm->getState();

        foreach ($data as $key => $value) {
            PlatformSetting::setValue($key, $value);
        }

        Notification::make()
            ->title('Paramètres de réservation enregistrés')
            ->success()
            ->send();
    }

    public function pricingForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tarification')
                    ->description('Taxes et frais visibles côté réservation')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\TextInput::make('state_tax')
                            ->label('Taxe d\'État')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->required()
                            ->helperText('Montant fixe de la taxe d\'État facturée au locataire par réservation'),
                    ])->columns(1),
            ])
            ->statePath('pricingData');
    }

    public function savePricing(): void
    {
        $data = $this->pricingForm->getState();

        foreach ($data as $key => $value) {
            PlatformSetting::setValue($key, $value);
        }

        Notification::make()
            ->title('Paramètres de tarification enregistrés')
            ->success()
            ->send();
    }

    public function saveGeneral(): void
    {
        $data = $this->generalForm->getState();

        foreach ($data as $key => $value) {
            PlatformSetting::setValue($key, $value);
        }

        Notification::make()
            ->title('Paramètres généraux enregistrés')
            ->success()
            ->send();
    }

    public function defaultsForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Valeurs par défaut des résidences')
                    ->description('Valeurs appliquées lorsqu\'une résidence ne renseigne pas ces champs')
                    ->icon('heroicon-o-home')
                    ->schema([
                        Forms\Components\TextInput::make('default_check_in_time')
                            ->label('Heure d\'arrivée par défaut')
                            ->placeholder('14h00')
                            ->maxLength(10)
                            ->required()
                            ->helperText('Format libre : 14h00, 14:00, etc.'),
                        Forms\Components\TextInput::make('default_check_out_time')
                            ->label('Heure de départ par défaut')
                            ->placeholder('12h00')
                            ->maxLength(10)
                            ->required()
                            ->helperText('Format libre : 12h00, 12:00, etc.'),
                        Forms\Components\TextInput::make('default_min_nights')
                            ->label('Séjour minimum (nuits)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365)
                            ->suffix('nuits')
                            ->required(),
                        Forms\Components\TextInput::make('default_max_nights')
                            ->label('Séjour maximum (nuits)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(730)
                            ->suffix('nuits')
                            ->required(),
                        Forms\Components\TextInput::make('default_max_guests')
                            ->label('Capacité par défaut')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->suffix('voyageurs')
                            ->required(),
                        Forms\Components\TextInput::make('default_city')
                            ->label('Ville par défaut')
                            ->maxLength(100)
                            ->required()
                            ->helperText('Utilisée dans les métadonnées SEO quand la ville n\'est pas renseignée'),
                    ])->columns(3),
            ])
            ->statePath('defaultsData');
    }

    public function saveDefaults(): void
    {
        $data = $this->defaultsForm->getState();

        foreach ($data as $key => $value) {
            PlatformSetting::setValue($key, $value);
        }

        Notification::make()
            ->title('Valeurs par défaut enregistrées')
            ->success()
            ->send();
    }
}
