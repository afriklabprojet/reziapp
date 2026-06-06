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

class SeoSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationGroup = 'Paramètres';

    protected static ?string $navigationLabel = 'Données SEO';

    protected static ?string $title = 'Données SEO';

    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.pages.seo-settings';

    public ?array $seoData = [];

    public function mount(): void
    {
        $s = PlatformSetting::all()->keyBy('key');

        $this->seoData = [
            'seo_site_title'         => $s->get('seo_site_title')?->value         ?? 'ReziApp – Résidences meublées à Abidjan',
            'seo_site_description'   => $s->get('seo_site_description')?->value   ?? '',
            'seo_site_keywords'      => $s->get('seo_site_keywords')?->value      ?? '',
            'seo_og_image'           => $s->get('seo_og_image')?->value           ?? '',
            'seo_google_analytics'   => $s->get('seo_google_analytics')?->value   ?? '',
            'seo_google_tag_manager' => $s->get('seo_google_tag_manager')?->value ?? '',
            'seo_robots'             => $s->get('seo_robots')?->value             ?? 'index, follow',
            'seo_canonical_domain'   => $s->get('seo_canonical_domain')?->value   ?? 'https://reziapp.ci',
        ];

        $this->seoForm->fill($this->seoData);
    }

    protected function getForms(): array
    {
        return ['seoForm'];
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
                            ->placeholder('ReziApp – Résidences meublées à Abidjan')
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

    public function save(): void
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
