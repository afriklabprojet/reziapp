<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Signalements de fraude et comportements suspects
     */
    public function up(): void
    {
        Schema::create('fraud_reports', function (Blueprint $table) {
            $table->id();

            // Qui signale
            $table->foreignId('reporter_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('reporter_ip')->nullable();
            $table->string('reporter_user_agent')->nullable();

            // Cible du signalement
            $table->enum('target_type', ['user', 'residence', 'review', 'message', 'contact']);
            $table->unsignedBigInteger('target_id');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->onDelete('set null'); // Propriétaire de la cible

            // Type de fraude
            $table->enum('fraud_type', [
                'fake_identity',        // Fausse identité
                'fake_listing',         // Annonce fictive
                'scam',                 // Arnaque
                'spam',                 // Spam
                'harassment',           // Harcèlement
                'fake_review',          // Faux avis
                'price_manipulation',   // Manipulation de prix
                'duplicate_listing',    // Annonce dupliquée
                'misleading_photos',    // Photos trompeuses
                'wrong_location',       // Mauvaise localisation
                'no_show',              // Ne se présente pas
                'payment_fraud',        // Fraude au paiement
                'other',
            ]);

            $table->text('description');
            $table->json('evidence')->nullable(); // URLs des preuves, screenshots

            // Score de risque automatique
            $table->unsignedTinyInteger('risk_score')->default(0); // 0-100
            $table->json('risk_factors')->nullable(); // Facteurs de risque détectés

            // Détection automatique
            $table->boolean('is_auto_detected')->default(false);
            $table->string('detection_rule')->nullable(); // Règle qui a détecté

            // Statut
            $table->enum('status', [
                'pending',          // En attente
                'investigating',    // En cours d'investigation
                'confirmed',        // Fraude confirmée
                'dismissed',        // Rejeté
                'resolved',          // Résolu
            ])->default('pending');

            // Actions prises
            $table->json('actions_taken')->nullable(); // Liste des actions
            $table->text('resolution_notes')->nullable();

            // Modération
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();

            // Priorité
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');

            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index(['target_user_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index('fraud_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_reports');
    }
};
