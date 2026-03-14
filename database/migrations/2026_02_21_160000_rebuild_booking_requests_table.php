<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Correction: la migration 2026_01_30_240002 créait booking_requests comme une table de log
     * (booking_id, action, action_by...) alors que le modèle BookingRequest attend une vraie
     * table de demandes de réservation (residence_id, check_in, check_out, status...).
     * On drop + rebuild avec le bon schéma.
     */
    public function up(): void
    {
        Schema::dropIfExists('booking_requests');

        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');

            // Dates de séjour
            $table->date('check_in');
            $table->date('check_out');

            // Voyageurs
            $table->unsignedTinyInteger('guests')->default(1);
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->unsignedTinyInteger('infants')->default(0);

            // Communication
            $table->text('message')->nullable();
            $table->json('special_requests')->nullable();

            // Tarification
            $table->decimal('price_per_night', 12, 2)->default(0);
            $table->unsignedSmallInteger('total_nights')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('cleaning_fee', 12, 2)->default(0);
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->decimal('long_stay_discount', 12, 2)->default(0);
            $table->decimal('promo_discount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            // Statut & réponse propriétaire
            $table->string('status')->default('pending'); // pending, approved, rejected, expired, converted
            $table->text('owner_response')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Index pour les requêtes fréquentes
            $table->index(['residence_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index(['residence_id', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_requests');

        // Recréer l'ancien schéma (log) en rollback
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('action');
            $table->foreignId('action_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('message')->nullable();
            $table->text('reason')->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'action']);
        });
    }
};
