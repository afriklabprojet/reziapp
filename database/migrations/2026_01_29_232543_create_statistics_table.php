<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Table des statistiques détaillées par jour
     */
    public function up(): void
    {
        Schema::create('statistics', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');

            // Date des stats
            $table->date('stat_date');

            // Compteurs journaliers
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('contacts')->default(0);
            $table->unsignedInteger('shares')->default(0);
            $table->unsignedInteger('favorites')->default(0);

            // Recherches géolocalisées
            $table->unsignedInteger('geo_searches')->default(0);
            $table->unsignedInteger('map_views')->default(0);

            // Origine des visites
            $table->unsignedInteger('mobile_views')->default(0);
            $table->unsignedInteger('desktop_views')->default(0);

            $table->timestamps();

            // Index composite unique pour éviter les doublons
            $table->unique(['residence_id', 'stat_date']);

            // Index pour les rapports
            $table->index('stat_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics');
    }
};
