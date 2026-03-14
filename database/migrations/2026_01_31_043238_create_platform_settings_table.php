<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, float, boolean, json
            $table->string('group')->default('general'); // general, commission, payment, notification
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        // Insérer les paramètres par défaut
        $settings = [
            // Commissions
            ['key' => 'commission_rate', 'value' => '10', 'type' => 'float', 'group' => 'commission', 'label' => 'Taux de commission (%)', 'description' => 'Pourcentage prélevé sur chaque réservation'],
            ['key' => 'commission_min', 'value' => '1000', 'type' => 'integer', 'group' => 'commission', 'label' => 'Commission minimum (FCFA)', 'description' => 'Montant minimum de commission par réservation'],
            ['key' => 'owner_payout_delay', 'value' => '48', 'type' => 'integer', 'group' => 'commission', 'label' => 'Délai de versement (heures)', 'description' => 'Délai après le check-in pour verser aux propriétaires'],

            // Paiement
            ['key' => 'min_booking_amount', 'value' => '5000', 'type' => 'integer', 'group' => 'payment', 'label' => 'Montant minimum (FCFA)', 'description' => 'Montant minimum pour une réservation'],
            ['key' => 'max_booking_amount', 'value' => '10000000', 'type' => 'integer', 'group' => 'payment', 'label' => 'Montant maximum (FCFA)', 'description' => 'Montant maximum pour une réservation'],
            ['key' => 'payment_methods', 'value' => '["orange_money","mtn_money","wave","card"]', 'type' => 'json', 'group' => 'payment', 'label' => 'Moyens de paiement', 'description' => 'Moyens de paiement activés'],

            // Réservations
            ['key' => 'min_booking_days', 'value' => '1', 'type' => 'integer', 'group' => 'booking', 'label' => 'Durée minimum (jours)', 'description' => 'Durée minimum de réservation'],
            ['key' => 'max_booking_days', 'value' => '365', 'type' => 'integer', 'group' => 'booking', 'label' => 'Durée maximum (jours)', 'description' => 'Durée maximum de réservation'],
            ['key' => 'advance_booking_days', 'value' => '180', 'type' => 'integer', 'group' => 'booking', 'label' => 'Réservation à l\'avance (jours)', 'description' => 'Nombre de jours maximum à l\'avance'],

            // Général
            ['key' => 'platform_name', 'value' => 'REZI', 'type' => 'string', 'group' => 'general', 'label' => 'Nom de la plateforme', 'is_public' => true],
            ['key' => 'platform_email', 'value' => 'contact@rezi.ci', 'type' => 'string', 'group' => 'general', 'label' => 'Email de contact', 'is_public' => true],
            ['key' => 'platform_phone', 'value' => '+225 07 00 00 00 00', 'type' => 'string', 'group' => 'general', 'label' => 'Téléphone', 'is_public' => true],
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'general', 'label' => 'Mode maintenance'],
        ];

        foreach ($settings as $setting) {
            DB::table('platform_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
