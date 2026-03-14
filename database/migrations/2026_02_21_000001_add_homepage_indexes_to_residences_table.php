<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajouter des indexes composites pour optimiser les requêtes homepage
 *
 * Les scopes Residence::approved()->available() sont utilisés partout.
 * L'index composite (status, is_available) accélère ces requêtes.
 * L'index géo couvre les requêtes de recherche par rayon.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            // Index composite pour les requêtes homepage (approved + available)
            $table->index(['status', 'is_available'], 'idx_residences_status_available');

            // Index composite pour les requêtes géolocalisées
            $table->index(
                ['status', 'is_available', 'latitude', 'longitude'],
                'idx_residences_geo_search'
            );

            // Index pour le tri par commune (zones populaires)
            $table->index(['status', 'commune'], 'idx_residences_commune');
        });
    }

    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropIndex('idx_residences_status_available');
            $table->dropIndex('idx_residences_geo_search');
            $table->dropIndex('idx_residences_commune');
        });
    }
};
