<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Blacklist des utilisateurs bannis
     */
    public function up(): void
    {
        Schema::create('blacklists', function (Blueprint $table) {
            $table->id();

            // Type de blacklist
            $table->enum('type', ['user', 'email', 'phone', 'ip', 'device', 'document']);

            // Valeur à blacklister
            $table->string('value'); // email, phone, IP, device_id, document_number
            $table->string('value_hash')->nullable(); // Hash pour recherche rapide

            // Utilisateur associé (si applicable)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Raison
            $table->enum('reason', [
                'fraud',            // Fraude avérée
                'scam',             // Arnaque
                'harassment',       // Harcèlement
                'spam',             // Spam répété
                'fake_identity',    // Fausse identité
                'payment_default',  // Défaut de paiement
                'terms_violation',  // Violation CGU
                'legal_request',    // Demande légale
                'other',
            ]);

            $table->text('description')->nullable();
            $table->json('evidence')->nullable(); // Preuves

            // Durée
            $table->boolean('is_permanent')->default(false);
            $table->timestamp('expires_at')->nullable(); // Null = permanent

            // Niveau de restriction
            $table->enum('restriction_level', [
                'warning',      // Avertissement
                'limited',      // Fonctionnalités limitées
                'suspended',    // Suspendu temporairement
                'banned',        // Banni définitivement
            ])->default('banned');

            // Statut
            $table->boolean('is_active')->default(true);

            // Qui a blacklisté
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            // Appel
            $table->boolean('appeal_allowed')->default(true);
            $table->text('appeal_message')->nullable();
            $table->timestamp('appeal_submitted_at')->nullable();
            $table->enum('appeal_status', ['none', 'pending', 'approved', 'rejected'])->default('none');
            $table->text('appeal_response')->nullable();
            $table->foreignId('appeal_reviewed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            $table->index(['type', 'value_hash']);
            $table->index(['user_id', 'is_active']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklists');
    }
};
