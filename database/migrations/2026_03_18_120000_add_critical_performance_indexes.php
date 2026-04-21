<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Ajout d'index critiques pour les performances de réservation et recherche.
     *
     * - bookings: empêche les scans complets lors de la vérification de disponibilité (avec lockForUpdate)
     * - residences: optimise le filtrage par type_location et par ville
     */
    public function up(): void
    {
        // Guard: skip if indexes already exist (partial migration recovery)
        $bookingIndexes = collect(Schema::getIndexes('bookings'))->pluck('name')->toArray();
        if (!in_array('bookings_availability_check_index', $bookingIndexes)) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->index(['residence_id', 'status', 'check_in', 'check_out'], 'bookings_availability_check_index');
            });
        }

        // Index pour le filtrage par type_location (zones populaires, page d'accueil)
        if (Schema::hasColumn('residences', 'type_location')) {
            $residenceIndexes = collect(Schema::getIndexes('residences'))->pluck('name')->toArray();
            if (!in_array('residences_type_location_index', $residenceIndexes)) {
                Schema::table('residences', function (Blueprint $table) {
                    $table->index(['status', 'is_available', 'type_location'], 'residences_type_location_index');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_availability_check_index');
        });

        Schema::table('residences', function (Blueprint $table) {
            $table->dropIndex('residences_type_location_index');
        });
    }
};
