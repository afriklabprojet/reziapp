<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajouter un index FULLTEXT pour la recherche textuelle.
 *
 * Remplace les requêtes LIKE %...% sur name, commune, quartier, description
 * par un index FULLTEXT natif MySQL qui est 10-100x plus rapide.
 */
return new class () extends Migration {
    public function up(): void
    {
        // FULLTEXT n'est pas supporté par SQLite
        if (config('database.default') === 'sqlite') {
            return;
        }

        Schema::table('residences', function (Blueprint $table) {
            $table->fullText(['name', 'commune', 'quartier', 'description'], 'ft_residences_search');
        });
    }

    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }

        Schema::table('residences', function (Blueprint $table) {
            $table->dropFullText('ft_residences_search');
        });
    }
};
