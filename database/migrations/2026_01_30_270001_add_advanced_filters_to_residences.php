<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     * Ajout des champs pour les filtres avancés
     */
    public function up(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            // Réservation instantanée
            if (!Schema::hasColumn('residences', 'instant_book')) {
                $table->boolean('instant_book')->default(false)->after('is_available');
            }

            // Politique d'annulation
            if (!Schema::hasColumn('residences', 'cancellation_policy_id')) {
                $table->foreignId('cancellation_policy_id')->nullable()->after('instant_book')
                    ->constrained('cancellation_policies')->nullOnDelete();
            }

            // Accessibilité PMR (Personnes à Mobilité Réduite)
            if (!Schema::hasColumn('residences', 'is_accessible')) {
                $table->boolean('is_accessible')->default(false)->after('cancellation_policy_id');
            }

            // Détails accessibilité
            if (!Schema::hasColumn('residences', 'accessibility_features')) {
                $table->json('accessibility_features')->nullable()->after('is_accessible');
            }

            // Disponibilité immédiate
            if (!Schema::hasColumn('residences', 'available_from')) {
                $table->date('available_from')->nullable()->after('is_available');
            }

            // Minimum de nuits
            if (!Schema::hasColumn('residences', 'min_nights')) {
                $table->integer('min_nights')->default(1)->after('max_guests');
            }

            // Maximum de nuits
            if (!Schema::hasColumn('residences', 'max_nights')) {
                $table->integer('max_nights')->nullable()->after('min_nights');
            }

            // Index pour les recherches
            $table->index('instant_book');
            $table->index('is_accessible');
            $table->index('available_from');
            $table->index('cancellation_policy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropIndex(['instant_book']);
            $table->dropIndex(['is_accessible']);
            $table->dropIndex(['available_from']);
            $table->dropIndex(['cancellation_policy_id']);

            $table->dropConstrainedForeignId('cancellation_policy_id');
            $table->dropColumn([
                'instant_book',
                'is_accessible',
                'accessibility_features',
                'available_from',
                'min_nights',
                'max_nights',
            ]);
        });
    }
};
