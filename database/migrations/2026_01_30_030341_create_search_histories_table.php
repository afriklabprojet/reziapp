<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Critères de recherche
            $table->string('commune')->nullable();
            $table->decimal('min_price', 12, 2)->nullable();
            $table->decimal('max_price', 12, 2)->nullable();
            $table->integer('bedrooms')->nullable();
            $table->string('type')->nullable(); // appartement, studio, villa
            $table->json('amenities')->nullable(); // équipements recherchés

            // Géolocalisation
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('radius')->nullable(); // en km

            // Résultats
            $table->integer('results_count')->default(0);
            $table->string('search_query')->nullable(); // texte de recherche libre

            $table->timestamps();

            // Index
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_histories');
    }
};
