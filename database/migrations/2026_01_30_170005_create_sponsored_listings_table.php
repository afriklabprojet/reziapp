<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Mises en avant sponsorisées
     */
    public function up(): void
    {
        Schema::create('sponsored_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // propriétaire

            // Type de sponsoring
            $table->enum('type', [
                'featured_home',    // Mis en avant sur la page d'accueil
                'top_search',       // En haut des résultats de recherche
                'highlighted',      // Badge "Sponsorisé"
                'premium_listing',  // Toutes les options
            ])->default('highlighted');

            // Période
            $table->datetime('starts_at');
            $table->datetime('ends_at');

            // Position (pour le tri dans les résultats)
            $table->integer('position')->default(1);

            // Budget et facturation
            $table->decimal('daily_budget', 10, 2)->nullable();
            $table->decimal('total_budget', 10, 2)->nullable();
            $table->decimal('amount_spent', 10, 2)->default(0);
            $table->enum('billing_type', ['flat_rate', 'per_view', 'per_click'])->default('flat_rate');
            $table->decimal('cost_per_unit', 10, 2)->default(0); // Prix par vue/clic

            // Statistiques
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('contacts_generated')->default(0);

            // Ciblage optionnel
            $table->json('target_communes')->nullable();
            $table->json('target_user_types')->nullable(); // clients, owners

            // Statut
            $table->enum('status', ['pending', 'active', 'paused', 'completed', 'cancelled'])->default('pending');
            $table->boolean('is_paid')->default(false);
            $table->string('payment_reference')->nullable();

            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
            $table->index('status');
            $table->index(['type', 'status']);
        });

        // Table pour les tarifs de sponsoring
        Schema::create('sponsorship_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "7 jours Accueil", "30 jours Premium"
            $table->text('description')->nullable();

            $table->enum('type', ['featured_home', 'top_search', 'highlighted', 'premium_listing']);
            $table->integer('duration_days');
            $table->decimal('price', 10, 2);

            $table->json('features')->nullable(); // Liste des avantages
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);

            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsorship_plans');
        Schema::dropIfExists('sponsored_listings');
    }
};
