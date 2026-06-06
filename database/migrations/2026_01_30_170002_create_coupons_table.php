<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Coupons - Codes de réduction personnalisés
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // créateur (admin ou proprio)
            $table->foreignId('residence_id')->nullable()->constrained()->onDelete('cascade'); // si spécifique à une résidence

            $table->string('code', 50)->unique(); // ReziApp2026, WELCOME20, etc.
            $table->string('name'); // Nom descriptif
            $table->text('description')->nullable();

            // Type de réduction
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('max_discount', 10, 2)->nullable(); // Plafond si pourcentage

            // Validité
            $table->datetime('starts_at')->nullable();
            $table->datetime('expires_at')->nullable();

            // Limitations
            $table->integer('max_uses')->nullable(); // Total
            $table->integer('max_uses_per_user')->default(1); // Par utilisateur
            $table->integer('uses_count')->default(0);

            // Conditions
            $table->decimal('min_amount', 10, 2)->nullable(); // Montant minimum de réservation
            $table->integer('min_nights')->nullable(); // Nuits minimum
            $table->boolean('first_booking_only')->default(false); // Nouveaux clients uniquement

            // Ciblage
            $table->json('allowed_communes')->nullable(); // Communes spécifiques
            $table->json('allowed_types')->nullable(); // Types de résidences
            $table->json('allowed_user_ids')->nullable(); // Utilisateurs spécifiques

            $table->enum('scope', ['global', 'residence', 'owner', 'user'])->default('global');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('code');
            $table->index(['starts_at', 'expires_at']);
        });

        // Table pivot pour suivre l'utilisation des coupons
        Schema::create('coupon_uses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null'); // la réservation
            $table->decimal('discount_applied', 10, 2);
            $table->timestamps();

            $table->unique(['coupon_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_uses');
        Schema::dropIfExists('coupons');
    }
};
