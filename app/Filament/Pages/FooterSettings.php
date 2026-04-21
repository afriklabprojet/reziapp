<?php

namespace App\Filament\Pages;

use App\Models\PlatformSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class FooterSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Contenu';

    protected static ?string $navigationLabel = 'Paramètres footer';

    protected static ?string $title = 'Paramètres du footer';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.footer-settings';

    public ?array $newsletterData = [];
    public ?array $statsData = [];
    public ?array $brandData = [];
    public ?array $socialData = [];

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $s = PlatformSetting::all()->keyBy('key');

        $this->newsletterData = [
            'footer_newsletter_enabled'  => (bool) ($s->get('footer_newsletter_enabled')?->value ?? true),
            'footer_newsletter_title'    => $s->get('footer_newsletter_title')?->value ?? 'Restez informé',
            'footer_newsletter_subtitle' => $s->get('footer_newsletter_subtitle')?->value ?? 'Recevez les nouvelles résidences et offres exclusives directement dans votre boîte mail.',
        ];

        $this->statsData = [
            'footer_stats_enabled'          => (bool) ($s->get('footer_stats_enabled')?->value ?? true),
            'footer_stats_residences_label' => $s->get('footer_stats_residences_label')?->value ?? 'Résidences vérifiées',
            'footer_stats_communes_label'   => $s->get('footer_stats_communes_label')?->value ?? 'Communes couvertes',
            'footer_stats_owners_label'     => $s->get('footer_stats_owners_label')?->value ?? 'Propriétaires actifs',
        ];

        $this->brandData = [
            'footer_brand_description' => $s->get('footer_brand_description')?->value ?? "La plateforme de référence pour trouver votre résidence meublée en Afrique de l'Ouest.",
            'footer_support_enabled'   => (bool) ($s->get('footer_support_enabled')?->value ?? true),
            'footer_support_text'      => $s->get('footer_support_text')?->value ?? 'Support en ligne 24/7',
        ];

        $this->socialData = [
            'footer_social_facebook_enabled'  => (bool) ($s->get('footer_social_facebook_enabled')?->value ?? true),
            'footer_social_instagram_enabled' => (bool) ($s->get('footer_social_instagram_enabled')?->value ?? true),
            'footer_social_whatsapp_enabled'  => (bool) ($s->get('footer_social_whatsapp_enabled')?->value ?? true),
            'footer_social_twitter_enabled'   => (bool) ($s->get('footer_social_twitter_enabled')?->value ?? true),
            'footer_social_linkedin_enabled'  => (bool) ($s->get('footer_social_linkedin_enabled')?->value ?? true),
            'footer_social_tiktok_enabled'    => (bool) ($s->get('footer_social_tiktok_enabled')?->value ?? true),
        ];
    }

    protected function getForms(): array
    {
        return ['newsletterForm', 'statsForm', 'brandForm', 'socialForm'];
    }

    // ─── Forms ────────────────────────────────────────────────

    public function newsletterForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Newsletter CTA')
                    ->description('Bandeau de capture email affiché en tête du footer')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Forms\Components\Toggle::make('footer_newsletter_enabled')
                            ->label('Activer la section newsletter')
                            ->onColor('success')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('footer_newsletter_title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(80),
                        Forms\Components\Textarea::make('footer_newsletter_subtitle')
                            ->label('Texte descriptif')
                            ->rows(2)
                            ->maxLength(200)
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('newsletterData');
    }

    public function statsForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Strip de chiffres-clés')
                    ->description('Bande de statistiques affichée sous la newsletter')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Forms\Components\Toggle::make('footer_stats_enabled')
                            ->label('Activer le strip de chiffres')
                            ->onColor('success')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('footer_stats_residences_label')
                            ->label('Label — Résidences')
                            ->required()
                            ->maxLength(40),
                        Forms\Components\TextInput::make('footer_stats_communes_label')
                            ->label('Label — Communes')
                            ->required()
                            ->maxLength(40),
                        Forms\Components\TextInput::make('footer_stats_owners_label')
                            ->label('Label — Propriétaires')
                            ->required()
                            ->maxLength(40),
                    ])->columns(3),
            ])
            ->statePath('statsData');
    }

    public function brandForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identité de marque')
                    ->description('Texte sous le logo et badge de support')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Forms\Components\Textarea::make('footer_brand_description')
                            ->label('Description de la marque')
                            ->rows(3)
                            ->maxLength(300)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('footer_support_enabled')
                            ->label('Afficher le badge support')
                            ->onColor('success'),
                        Forms\Components\TextInput::make('footer_support_text')
                            ->label('Texte du badge support')
                            ->maxLength(50),
                    ])->columns(2),
            ])
            ->statePath('brandData');
    }

    public function socialForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Réseaux sociaux')
                    ->description('Activer ou désactiver chaque icône de réseau social dans le footer')
                    ->icon('heroicon-o-share')
                    ->schema([
                        Forms\Components\Toggle::make('footer_social_facebook_enabled')
                            ->label('Facebook')
                            ->onColor('success'),
                        Forms\Components\Toggle::make('footer_social_instagram_enabled')
                            ->label('Instagram')
                            ->onColor('success'),
                        Forms\Components\Toggle::make('footer_social_whatsapp_enabled')
                            ->label('WhatsApp')
                            ->onColor('success'),
                        Forms\Components\Toggle::make('footer_social_twitter_enabled')
                            ->label('X (Twitter)')
                            ->onColor('success'),
                        Forms\Components\Toggle::make('footer_social_linkedin_enabled')
                            ->label('LinkedIn')
                            ->onColor('success'),
                        Forms\Components\Toggle::make('footer_social_tiktok_enabled')
                            ->label('TikTok')
                            ->onColor('success'),
                    ])->columns(3),
            ])
            ->statePath('socialData');
    }

    // ─── Save actions ─────────────────────────────────────────

    public function saveNewsletter(): void
    {
        foreach ($this->newsletterForm->getState() as $key => $value) {
            PlatformSetting::setValue($key, $value);
        }
        Cache::forget('footer_all_settings');
        Notification::make()->title('Section newsletter mise à jour')->success()->send();
    }

    public function saveStats(): void
    {
        foreach ($this->statsForm->getState() as $key => $value) {
            PlatformSetting::setValue($key, $value);
        }
        Cache::forget('footer_all_settings');
        Notification::make()->title('Strip de chiffres mis à jour')->success()->send();
    }

    public function saveBrand(): void
    {
        foreach ($this->brandForm->getState() as $key => $value) {
            PlatformSetting::setValue($key, $value);
        }
        Cache::forget('footer_all_settings');
        Notification::make()->title('Identité de marque mise à jour')->success()->send();
    }

    public function saveSocial(): void
    {
        foreach ($this->socialForm->getState() as $key => $value) {
            PlatformSetting::setValue($key, $value);
        }
        Cache::forget('footer_all_settings');
        Notification::make()->title('Réseaux sociaux mis à jour')->success()->send();
    }
}
