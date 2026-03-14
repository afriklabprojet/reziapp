<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Vérification de résidence (preuve d'adresse)
     */
    public function up(): void
    {
        Schema::create('residence_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Propriétaire

            // Type de vérification
            $table->enum('verification_type', [
                'document',     // Document justificatif (facture, titre)
                'visit',        // Visite sur place
                'video_call',   // Appel vidéo
                'gps_check',     // Vérification GPS
            ])->default('document');

            // Documents
            $table->string('proof_document')->nullable(); // Facture, titre de propriété
            $table->enum('document_type', [
                'utility_bill',      // Facture CIE/SODECI
                'property_title',    // Titre foncier
                'rental_contract',   // Contrat de bail
                'tax_receipt',       // Quittance impôts fonciers
                'other',
            ])->nullable();

            // Vérification GPS
            $table->decimal('verified_latitude', 10, 8)->nullable();
            $table->decimal('verified_longitude', 11, 8)->nullable();
            $table->decimal('gps_accuracy', 8, 2)->nullable(); // Précision en mètres
            $table->decimal('distance_from_declared', 8, 2)->nullable(); // Distance de l'adresse déclarée

            // Photos de vérification
            $table->json('verification_photos')->nullable(); // Photos prises sur place

            // Visite physique
            $table->timestamp('visit_scheduled_at')->nullable();
            $table->timestamp('visit_completed_at')->nullable();
            $table->text('visit_notes')->nullable();
            $table->foreignId('visited_by')->nullable()->constrained('users')->onDelete('set null');

            // Statut
            $table->enum('status', [
                'pending',
                'documents_submitted',
                'visit_scheduled',
                'under_review',
                'approved',
                'rejected',
                'expired',
            ])->default('pending');

            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();

            // Modérateur
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['residence_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residence_verifications');
    }
};
