<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Vérification d'identité (CNI / Passeport)
     */
    public function up(): void
    {
        Schema::create('identity_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Type de document
            $table->enum('document_type', ['cni', 'passport', 'driver_license', 'residence_permit'])->default('cni');
            $table->string('document_number')->nullable(); // Numéro du document (crypté)
            $table->string('document_country', 2)->default('CI'); // Code pays ISO

            // Fichiers
            $table->string('document_front')->nullable(); // Recto du document
            $table->string('document_back')->nullable(); // Verso du document
            $table->string('selfie_photo')->nullable(); // Photo selfie

            // Informations extraites
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('document_expiry')->nullable();
            $table->json('extracted_data')->nullable(); // Données OCR brutes

            // Vérification selfie
            $table->decimal('face_match_score', 5, 2)->nullable(); // Score de correspondance 0-100
            $table->boolean('face_match_passed')->default(false);

            // Statut
            $table->enum('status', [
                'pending',      // En attente de soumission
                'submitted',    // Documents soumis
                'processing',   // En cours de traitement
                'manual_review', // Révision manuelle requise
                'approved',     // Approuvé
                'rejected',     // Rejeté
                'expired',       // Document expiré
            ])->default('pending');

            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();

            // Modérateur
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Expiration de la vérification

            // Tentatives
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('document_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_verifications');
    }
};
