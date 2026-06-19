<?php

use App\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        $settings = [
            ['key' => 'default_check_in_time',  'value' => '14h00', 'label' => 'Heure d\'arrivée par défaut',   'type' => 'string'],
            ['key' => 'default_check_out_time', 'value' => '12h00', 'label' => 'Heure de départ par défaut',    'type' => 'string'],
            ['key' => 'default_min_nights',     'value' => '1',     'label' => 'Séjour minimum par défaut',     'type' => 'integer'],
            ['key' => 'default_max_nights',     'value' => '365',   'label' => 'Séjour maximum par défaut',     'type' => 'integer'],
            ['key' => 'default_max_guests',     'value' => '4',     'label' => 'Capacité par défaut',           'type' => 'integer'],
            ['key' => 'default_city',           'value' => 'Abidjan', 'label' => 'Ville par défaut',            'type' => 'string'],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value'     => $setting['value'],
                    'type'      => $setting['type'],
                    'group'     => 'residence_defaults',
                    'label'     => $setting['label'],
                    'is_public' => false,
                ],
            );
        }
    }

    public function down(): void
    {
        PlatformSetting::whereIn('key', [
            'default_check_in_time',
            'default_check_out_time',
            'default_min_nights',
            'default_max_nights',
            'default_max_guests',
            'default_city',
        ])->delete();
    }
};
