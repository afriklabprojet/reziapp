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

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Paramètres site';

    protected static ?string $title = 'Paramètres du site';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.site-settings';

    public ?array $countryData = [];
    public ?array $seoData    = [];

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $s = PlatformSetting::all()->keyBy('key');

        $this->countryData = [
            'site_country'       => $s->get('site_country')?->value       ?? "Côte d'Ivoire",
            'site_country_code'  => $s->get('site_country_code')?->value  ?? 'CI',
            'site_currency'      => $s->get('site_currency')?->value      ?? 'FCFA',
            'site_currency_code' => $s->get('site_currency_code')?->value ?? 'XOF',
            'site_phone_prefix'  => $s->get('site_phone_prefix')?->value  ?? '+225',
            'site_city'          => $s->get('site_city')?->value          ?? 'Abidjan',
            'site_timezone'      => $s->get('site_timezone')?->value      ?? 'Africa/Abidjan',
            'site_locale'        => $s->get('site_locale')?->value        ?? 'fr',
        ];

        $this->seoData = [
            'seo_site_title'         => $s->get('seo_site_title')?->value         ?? 'REZI – Résidences meublées à Abidjan',
            'seo_site_description'   => $s->get('seo_site_description')?->value   ?? '',
            'seo_site_keywords'      => $s->get('seo_site_keywords')?->value      ?? '',
            'seo_og_image'           => $s->get('seo_og_image')?->value           ?? '',
            'seo_google_analytics'   => $s->get('seo_google_analytics')?->value   ?? '',
            'seo_google_tag_manager' => $s->get('seo_google_tag_manager')?->value ?? '',
            'seo_robots'             => $s->get('seo_robots')?->value             ?? 'index, follow',
            'seo_canonical_domain'   => $s->get('seo_canonical_domain')?->value   ?? 'https://reziapp.ci',
        ];
    }

    protected function getForms(): array
    {
        return ['countryForm', 'seoForm'];
    }

    public function countryForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pays & localisation')
                    ->description('Paramètres géographiques et régionaux du site')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\TextInput::make('site_country')
                            ->label('Pays')
                            ->required()
                            ->maxLength(60)
                            ->placeholder("Côte d'Ivoire"),

                        Forms\Components\TextInput::make('site_country_code')
                            ->label('Code ISO pays')
                            ->required()
                            ->maxLength(2)
                            ->placeholder('CI')
                            ->helperText('2 lettres majuscules (ISO 3166-1)'),

                        Forms\Components\TextInput::make('site_city')
                            ->label('Ville principale')
                            ->required()
                            ->maxLength(60)
                            ->placeholder('Abidjan'),

                        Forms\Components\TextInput::make('site_phone_prefix')
                            ->label('Indicatif téléphonique')
                            ->required()
                            ->maxLength(6)
                            ->placeholder('+225')
                            ->helperText('Ex : +225'),

                        Forms\Components\TextInput::make('site_currency')
                            ->label('Devise affichée')
                            ->required()
                            ->maxLength(10)
                            ->placeholder('FCFA'),

                        Forms\Components\TextInput::make('site_currency_code')
                            ->label('Code devise ISO')
                            ->required()
                            ->maxLength(3)
                            ->placeholder('XOF')
                            ->helperText('3 lettres majuscules (ISO 4217)'),

                        Forms\Components\Select::make('site_timezone')
                            ->label('Fuseau horaire')
                            ->required()
                            ->searchable()
                            ->options(collect(timezone_identifiers_list())->mapWithKeys(fn ($tz) => [$tz => $tz]))
                            ->placeholder('Africa/Abidjan'),

                        Forms\Components\Select::make('site_locale')
                            ->label('Langue')
                            ->required()
                            ->options([
                                'fr' => 'Français',
                                'en' => 'English',
                            ]),
                    ])->columns(2),
            ])
            ->statePath('countryData');
    }

    public function seoForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('SEO & méta-données')
                    ->description('Référencement et méta-données globales du site')
                    ->icon('heroicon-o-magnifying-glass')
                    ->schema([
                        Forms\Components\TextInput::make('seo_site_title')
                            ->label('Titre du site')
                            ->required()
                            ->maxLength(70)
                            ->placeholder('REZI – Résidences meublées à Abidjan')
                            ->helperText('Recommandé : 50–70 caractères')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('seo_site_description')
                            ->label('Méta description')
                            ->rows(3)
                            ->maxLength(160)
                            ->placeholder('Trouvez votre résidence meublée à Abidjan…')
                            ->helperText('Recommandé : 120–160 caractères')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('seo_site_keywords')
                            ->label('Mots-clés')
                            ->maxLength(255)
                            ->placeholder('résidence meublée, Abidjan, location…')
                            ->helperText('Séparés par des virgules')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('seo_og_image')
                            ->label('OG Image URL')
                            ->url()
                            ->maxLength(500)
                            ->placeholder('https://reziapp.ci/images/og-cover.jpg')
                            ->helperText('Image partagée sur Facebook/WhatsApp — 1200×630px'),

                        Forms\Components\TextInput::make('seo_canonical_domain')
                            ->label('Domaine canonique')
                            ->url()
                            ->required()
                            ->maxLength(100)
                            ->placeholder('https://reziapp.ci'),

                        Forms\Components\TextInput::make('seo_google_analytics')
                            ->label('Google Analytics ID')
                            ->maxLength(30)
                            ->placeholder('G-XXXXXXXXXX')
                            ->helperText('Format : G-XXXXXXXXXX ou UA-XXXXXXXX'),

                        Forms\Components\TextInput::make('seo_google_tag_manager')
                            ->label('Google Tag Manager ID')
                            ->maxLength(20)
                            ->placeholder('GTM-XXXXXXX')
                            ->helperText('Format : GTM-XXXXXXX'),

                        Forms\Components\Select::make('seo_robots')
                            ->label('Robots meta')
                            ->required()
                            ->options([
                                'index, follow'     => 'index, follow (recommandé)',
                                'noindex, follow'   => 'noindex, follow',
                                'index, nofollow'   => 'index, nofollow',
                                'noindex, nofollow' => 'noindex, nofollow (bloquer tout)',
                            ]),
                    ])->columns(2),
            ])
            ->statePath('seoData');
    }

    public function saveCountry(): void
    {
        $data = $this->countryForm->getState();

        foreach ($data as $key => $value) {
            PlatformSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => 'string', 'group' => 'site', 'is_public' => false]
            );
            Cache::forget("setting.{$key}");
        }

        Cache::forget('site_all_settings');

        Notification::make()
            ->title('Paramètres pays enregistrés')
            ->success()
            ->send();
    }

    public function saveSeo(): void
    {
        $data = $this->seoForm->getState();

        foreach ($data as $key => $value) {
            PlatformSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'type' => 'string', 'group' => 'seo', 'is_public' => false]
            );
            Cache::forget("setting.{$key}");
        }

        Cache::forget('seo_all_settings');

        Notification::make()
            ->title('Paramètres SEO enregistrés')
            ->success()
            ->send();
    }
}
