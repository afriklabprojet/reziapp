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
        // Table des plans d'abonnement
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                  // Nom du plan (Starter, Pro, Premium)
            $table->string('slug')->unique();                        // Identifiant unique
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2);                 // Prix mensuel en FCFA
            $table->decimal('price_yearly', 10, 2)->nullable();      // Prix annuel (réduction)
            $table->integer('max_residences')->default(1);           // Nombre max de résidences
            $table->integer('max_photos_per_residence')->default(10);
            $table->integer('max_sponsored_per_month')->default(0);  // Boosts gratuits par mois
            $table->decimal('commission_rate', 5, 2)->default(3.00); // Taux de commission ReziApp (%)
            $table->boolean('priority_support')->default(false);
            $table->boolean('analytics_advanced')->default(false);
            $table->boolean('auto_replies')->default(false);
            $table->boolean('calendar_sync')->default(false);
            $table->boolean('featured_badge')->default(false);       // Badge "Super Host"
            $table->json('features')->nullable();                    // Fonctionnalités additionnelles
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Table des abonnements utilisateurs
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'cancelled', 'expired', 'past_due', 'trialing'])->default('trialing');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->decimal('amount', 10, 2);                        // Montant facturé
            $table->string('payment_method')->nullable();            // Mode de paiement
            $table->string('payment_reference')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('current_period_end');
        });

        // Table des paiements d'abonnement
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('XOF');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_provider')->nullable();          // jeko, mobile_money, etc.
            $table->string('transaction_id')->nullable();
            $table->string('reference')->unique();
            $table->timestamp('paid_at')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
