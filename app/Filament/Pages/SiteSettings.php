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

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Paramètres';

    protected static ?string $navigationLabel = 'Localisation';

    protected static ?string $title = 'Paramètres localisation';

    protected static ?int $navigationSort = 12;

    protected static string $view = 'filament.pages.site-settings';

    public ?array $countryData = [];

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


    }

    protected function getForms(): array
    {
        return ['countryForm'];
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

}
