<?php

use App\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        $settings = [
            // --- Pays ---
            ['key' => 'site_country',       'value' => "Côte d'Ivoire", 'label' => 'Pays'],
            ['key' => 'site_country_code',  'value' => 'CI',            'label' => 'Code ISO pays'],
            ['key' => 'site_currency',      'value' => 'FCFA',          'label' => 'Devise affichée'],
            ['key' => 'site_currency_code', 'value' => 'XOF',           'label' => 'Code devise ISO'],
            ['key' => 'site_phone_prefix',  'value' => '+225',          'label' => 'Indicatif téléphonique'],
            ['key' => 'site_city',          'value' => 'Abidjan',       'label' => 'Ville principale'],
            ['key' => 'site_timezone',      'value' => 'Africa/Abidjan','label' => 'Fuseau horaire'],
            ['key' => 'site_locale',        'value' => 'fr',            'label' => 'Langue'],

            // --- SEO ---
            ['key' => 'seo_site_title',         'value' => 'ReziApp – Location de résidences meublés à Abidjan', 'label' => 'Titre du site'],
            ['key' => 'seo_site_description',   'value' => 'Trouvez votre résidence meublée à Abidjan. Courte et longue durée, toutes communes.', 'label' => 'Méta description'],
            ['key' => 'seo_site_keywords',      'value' => 'résidence meublée, Abidjan, location, appartement, studio, ReziApp', 'label' => 'Mots-clés'],
            ['key' => 'seo_og_image',           'value' => '',          'label' => 'OG Image URL'],
            ['key' => 'seo_google_analytics',   'value' => '',          'label' => 'Google Analytics ID'],
            ['key' => 'seo_google_tag_manager', 'value' => '',          'label' => 'Google Tag Manager ID'],
            ['key' => 'seo_robots',             'value' => 'index, follow', 'label' => 'Robots meta'],
            ['key' => 'seo_canonical_domain',   'value' => 'https://reziapp.ci', 'label' => 'Domaine canonique'],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value'     => $setting['value'],
                    'type'      => 'string',
                    'group'     => str_starts_with($setting['key'], 'seo_') ? 'seo' : 'site',
                    'is_public' => false,
                ],
            );
        }
    }

    public function down(): void
    {
        $keys = [
            'site_country', 'site_country_code', 'site_currency', 'site_currency_code',
            'site_phone_prefix', 'site_city', 'site_timezone', 'site_locale',
            'seo_site_title', 'seo_site_description', 'seo_site_keywords', 'seo_og_image',
            'seo_google_analytics', 'seo_google_tag_manager', 'seo_robots', 'seo_canonical_domain',
        ];

        PlatformSetting::whereIn('key', $keys)->delete();
    }
};
