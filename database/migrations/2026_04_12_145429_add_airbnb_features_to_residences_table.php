<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            // Frais de ménage (Airbnb-style)
            $table->decimal('cleaning_fee', 10, 2)->default(0)->after('price_per_month');

            // Score écologique / durabilité (0-100)
            $table->tinyInteger('sustainability_score')->unsigned()->nullable()->after('listing_quality_score');

            // Logement adapté aux voyages professionnels
            $table->boolean('is_work_travel_ready')->default(false)->after('is_accessible');

            // Compteur de réservations confirmées ce mois (pour badge de popularité)
            $table->smallInteger('bookings_this_month')->unsigned()->default(0)->after('views_count');

            // Nombre de vues actives (visiteurs uniques en 24h, mis à jour via job)
            $table->smallInteger('active_viewers_24h')->unsigned()->default(0)->after('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn([
                'cleaning_fee',
                'sustainability_score',
                'is_work_travel_ready',
                'bookings_this_month',
                'active_viewers_24h',
            ]);
        });
    }
};
