<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Promotions flash - Offres temporaires sur les résidences
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // propriétaire

            $table->string('title'); // "Promo Week-end", "Offre Nouvelle Année"
            $table->text('description')->nullable();

            // Type de réduction
            $table->enum('discount_type', ['percentage', 'fixed', 'free_nights']);
            $table->decimal('discount_value', 10, 2); // Ex: 20 pour 20% ou 5000 pour 5000 FCFA
            $table->integer('free_nights_min')->nullable(); // Pour "3 nuits = 4ème offerte"

            // Période de la promotion
            $table->datetime('starts_at');
            $table->datetime('ends_at');

            // Conditions
            $table->integer('min_nights')->default(1); // Nuits minimum pour bénéficier
            $table->integer('max_uses')->nullable(); // Nombre max d'utilisations
            $table->integer('uses_count')->default(0);

            // Période d'application (réservations pour quelles dates)
            $table->date('booking_start')->nullable(); // Réservations à partir de
            $table->date('booking_end')->nullable(); // Réservations jusqu'à

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false); // Mise en avant sur la page d'accueil

            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
