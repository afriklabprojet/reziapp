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
        // Table des plans d'assurance
        Schema::create('insurance_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                  // Nom (Basic, Standard, Premium)
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('rate', 5, 2);                           // Pourcentage du montant de réservation
            $table->decimal('min_amount', 10, 2)->default(0);        // Montant minimum
            $table->decimal('max_coverage', 10, 2);                  // Couverture maximale
            $table->integer('deductible')->default(0);               // Franchise en FCFA
            $table->json('coverage_types');                          // Types de couvertures incluses
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Table des assurances souscrites
        Schema::create('booking_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('premium_amount', 10, 2);                // Montant de la prime payée
            $table->decimal('coverage_amount', 10, 2);               // Montant de couverture
            $table->enum('status', ['active', 'claimed', 'expired', 'cancelled'])->default('active');
            $table->string('policy_number')->unique();               // Numéro de police
            $table->timestamp('coverage_start');
            $table->timestamp('coverage_end');
            $table->json('covered_items')->nullable();               // Détails des éléments couverts
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });

        // Table des réclamations d'assurance
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_insurance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('claim_number')->unique();
            $table->enum('claim_type', ['damage', 'theft', 'cancellation', 'accident', 'other']);
            $table->text('description');
            $table->decimal('claimed_amount', 10, 2);
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected', 'paid'])->default('submitted');
            $table->timestamp('incident_date');
            $table->json('evidence')->nullable();                    // Photos, documents
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('booking_insurances');
        Schema::dropIfExists('insurance_plans');
    }
};
