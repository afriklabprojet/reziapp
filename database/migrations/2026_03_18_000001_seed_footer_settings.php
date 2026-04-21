<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $settings = [
            // Newsletter CTA
            ['key' => 'footer_newsletter_enabled',  'value' => '1',    'type' => 'boolean', 'group' => 'footer', 'label' => 'Newsletter activée',           'description' => 'Afficher la section newsletter dans le footer', 'is_public' => false],
            ['key' => 'footer_newsletter_title',     'value' => 'Restez informé', 'type' => 'string', 'group' => 'footer', 'label' => 'Titre newsletter', 'description' => 'Titre de la section newsletter', 'is_public' => true],
            ['key' => 'footer_newsletter_subtitle',  'value' => 'Recevez les nouvelles résidences et offres exclusives directement dans votre boîte mail.', 'type' => 'string', 'group' => 'footer', 'label' => 'Sous-titre newsletter', 'description' => 'Texte descriptif sous le titre', 'is_public' => true],

            // Strip de chiffres-clés
            ['key' => 'footer_stats_enabled',           'value' => '1', 'type' => 'boolean', 'group' => 'footer', 'label' => 'Strip de chiffres activé',    'description' => 'Afficher la bande de statistiques', 'is_public' => false],
            ['key' => 'footer_stats_residences_label',  'value' => 'Résidences vérifiées',  'type' => 'string', 'group' => 'footer', 'label' => 'Label résidences',     'is_public' => true],
            ['key' => 'footer_stats_communes_label',    'value' => 'Communes couvertes',    'type' => 'string', 'group' => 'footer', 'label' => 'Label communes',       'is_public' => true],
            ['key' => 'footer_stats_owners_label',      'value' => 'Propriétaires actifs',  'type' => 'string', 'group' => 'footer', 'label' => 'Label propriétaires',  'is_public' => true],

            // Identité de marque
            ['key' => 'footer_brand_description',   'value' => "La plateforme de référence pour trouver votre résidence meublée en Afrique de l'Ouest. Des centaines de logements vérifiés à portée de clic.", 'type' => 'string', 'group' => 'footer', 'label' => 'Description de marque', 'description' => 'Texte sous le logo', 'is_public' => true],
            ['key' => 'footer_support_enabled',     'value' => '1', 'type' => 'boolean', 'group' => 'footer', 'label' => 'Badge support activé',    'description' => 'Afficher le badge "Support en ligne 24/7"', 'is_public' => false],
            ['key' => 'footer_support_text',        'value' => 'Support en ligne 24/7', 'type' => 'string', 'group' => 'footer', 'label' => 'Texte du badge support', 'is_public' => true],

            // Réseaux sociaux
            ['key' => 'footer_social_facebook_enabled',  'value' => '1', 'type' => 'boolean', 'group' => 'footer', 'label' => 'Facebook activé',   'is_public' => false],
            ['key' => 'footer_social_instagram_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'footer', 'label' => 'Instagram activé',  'is_public' => false],
            ['key' => 'footer_social_whatsapp_enabled',  'value' => '1', 'type' => 'boolean', 'group' => 'footer', 'label' => 'WhatsApp activé',   'is_public' => false],
            ['key' => 'footer_social_twitter_enabled',   'value' => '1', 'type' => 'boolean', 'group' => 'footer', 'label' => 'X (Twitter) activé','is_public' => false],
            ['key' => 'footer_social_linkedin_enabled',  'value' => '1', 'type' => 'boolean', 'group' => 'footer', 'label' => 'LinkedIn activé',   'is_public' => false],
            ['key' => 'footer_social_tiktok_enabled',    'value' => '1', 'type' => 'boolean', 'group' => 'footer', 'label' => 'TikTok activé',     'is_public' => false],
        ];

        foreach ($settings as $setting) {
            DB::table('platform_settings')->insertOrIgnore(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        DB::table('platform_settings')->whereIn('key', [
            'footer_newsletter_enabled', 'footer_newsletter_title', 'footer_newsletter_subtitle',
            'footer_stats_enabled', 'footer_stats_residences_label', 'footer_stats_communes_label', 'footer_stats_owners_label',
            'footer_brand_description', 'footer_support_enabled', 'footer_support_text',
            'footer_social_facebook_enabled', 'footer_social_instagram_enabled', 'footer_social_whatsapp_enabled',
            'footer_social_twitter_enabled', 'footer_social_linkedin_enabled', 'footer_social_tiktok_enabled',
        ])->delete();
    }
};
