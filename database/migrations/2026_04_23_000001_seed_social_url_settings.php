<?php

use App\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['key' => 'footer_social_facebook_url',  'value' => 'https://facebook.com/reziapp.ci',      'label' => 'URL Facebook',   'description' => 'Lien vers la page Facebook'],
            ['key' => 'footer_social_instagram_url', 'value' => 'https://instagram.com/reziapp.ci',     'label' => 'URL Instagram',  'description' => 'Lien vers le profil Instagram'],
            ['key' => 'footer_social_whatsapp_url',  'value' => 'https://wa.me/2250700000000',          'label' => 'URL WhatsApp',   'description' => 'Lien WhatsApp (format https://wa.me/NUMERO)'],
            ['key' => 'footer_social_twitter_url',   'value' => 'https://twitter.com/rezi_ci',          'label' => 'URL X (Twitter)','description' => 'Lien vers le profil X/Twitter'],
            ['key' => 'footer_social_linkedin_url',  'value' => 'https://linkedin.com/company/rezi-ci', 'label' => 'URL LinkedIn',   'description' => 'Lien vers la page LinkedIn'],
            ['key' => 'footer_social_tiktok_url',    'value' => 'https://tiktok.com/@reziapp.ci',       'label' => 'URL TikTok',     'description' => 'Lien vers le profil TikTok'],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value'       => $setting['value'],
                    'type'        => 'string',
                    'group'       => 'footer',
                    'label'       => $setting['label'],
                    'description' => $setting['description'],
                    'is_public'   => false,
                ]
            );
        }
    }

    public function down(): void
    {
        PlatformSetting::whereIn('key', [
            'footer_social_facebook_url',
            'footer_social_instagram_url',
            'footer_social_whatsapp_url',
            'footer_social_twitter_url',
            'footer_social_linkedin_url',
            'footer_social_tiktok_url',
        ])->delete();
    }
};
