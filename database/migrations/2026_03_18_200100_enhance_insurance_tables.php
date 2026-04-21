<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // ─── Enrichir insurance_subscriptions ─────────────────────────────
        Schema::table('insurance_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_subscriptions', 'external_policy_ref')) {
                $table->string('external_policy_ref')->nullable()->after('policy_number')
                    ->comment('Référence police chez l\'assureur partenaire (future API)');
            }
            if (!Schema::hasColumn('insurance_subscriptions', 'risk_score')) {
                $table->unsignedSmallInteger('risk_score')->nullable()->after('coverage_details')
                    ->comment('Score de risque calculé 0-100 (100 = risque max)');
            }
            if (!Schema::hasColumn('insurance_subscriptions', 'risk_factors')) {
                $table->json('risk_factors')->nullable()->after('risk_score')
                    ->comment('Détail des facteurs de risque utilisés pour le calcul');
            }
            if (!Schema::hasColumn('insurance_subscriptions', 'claim_count')) {
                $table->unsignedSmallInteger('claim_count')->default(0)->after('risk_factors')
                    ->comment('Nombre de sinistres déclarés sur ce contrat');
            }
            if (!Schema::hasColumn('insurance_subscriptions', 'suggested_premium')) {
                $table->decimal('suggested_premium', 10, 0)->nullable()->after('monthly_premium')
                    ->comment('Prime suggérée par le moteur de tarification');
            }
            if (!Schema::hasColumn('insurance_subscriptions', 'currency')) {
                $table->string('currency', 3)->default('XOF')->after('monthly_premium');
            }
            if (!Schema::hasColumn('insurance_subscriptions', 'cancellation_reason')) {
                $table->string('cancellation_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('insurance_subscriptions', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            }
            if (!Schema::hasColumn('insurance_subscriptions', 'renewed_from_id')) {
                $table->foreignId('renewed_from_id')->nullable()
                    ->constrained('insurance_subscriptions')->nullOnDelete()
                    ->comment('ID du contrat précédent en cas de renouvellement');
            }
        });

        // ─── Enrichir booking_insurances ──────────────────────────────────
        Schema::table('booking_insurances', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_insurances', 'external_policy_ref')) {
                $table->string('external_policy_ref')->nullable()->after('policy_number');
            }
            if (!Schema::hasColumn('booking_insurances', 'risk_score')) {
                $table->unsignedSmallInteger('risk_score')->nullable()->after('metadata');
            }
        });

        // ─── Enrichir insurance_claims ────────────────────────────────────
        Schema::table('insurance_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_claims', 'rejection_reason')) {
                $table->string('rejection_reason')->nullable()->after('admin_notes');
            }
            if (!Schema::hasColumn('insurance_claims', 'expert_amount')) {
                $table->decimal('expert_amount', 10, 2)->nullable()->after('approved_amount')
                    ->comment('Montant estimé par l\'expert (expertise contradictoire)');
            }
            if (!Schema::hasColumn('insurance_claims', 'deductible_applied')) {
                $table->decimal('deductible_applied', 10, 2)->default(0)->after('expert_amount')
                    ->comment('Franchise appliquée');
            }
            if (!Schema::hasColumn('insurance_claims', 'final_payment_amount')) {
                $table->decimal('final_payment_amount', 10, 2)->nullable()->after('deductible_applied')
                    ->comment('Montant réellement versé après franchise');
            }
            if (!Schema::hasColumn('insurance_claims', 'processing_deadline')) {
                $table->timestamp('processing_deadline')->nullable()->after('reviewed_at')
                    ->comment('Délai réglementaire CIMA: 45 jours');
            }
            if (!Schema::hasColumn('insurance_claims', 'external_claim_ref')) {
                $table->string('external_claim_ref')->nullable()->after('claim_number')
                    ->comment('Référence dossier chez l\'assureur partenaire');
            }
        });

        // ─── Table d'audit: insurance_events ──────────────────────────────
        if (!Schema::hasTable('insurance_events')) {
            Schema::create('insurance_events', function (Blueprint $table) {
                $table->id();
                $table->string('eventable_type');
                $table->unsignedBigInteger('eventable_id');
                $table->string('event_type', 100)
                    ->comment('souscription, renouvellement, résiliation, sinistre_soumis, sinistre_approuvé, etc.');
                $table->string('title');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable()
                    ->comment('Données contextuelles (montants, scores, raisons)');
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()
                    ->comment('Utilisateur ayant déclenché l\'événement');
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['eventable_type', 'eventable_id']);
                $table->index('event_type');
                $table->index('created_at');
            });
        }

        // ─── Table: insurance_partner_quotes ──────────────────────────────
        // Prépare l'intégration future avec API assureur (NSIA, SUNU, etc.)
        if (!Schema::hasTable('insurance_partner_quotes')) {
            Schema::create('insurance_partner_quotes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
                $table->string('partner_name', 100)->comment('NSIA, SUNU, ALLIANZ_CI, etc.');
                $table->string('coverage_type', 50);
                $table->decimal('quoted_premium', 10, 0);
                $table->decimal('coverage_amount', 12, 0);
                $table->json('coverage_details')->nullable();
                $table->unsignedSmallInteger('risk_score')->nullable();
                $table->json('risk_factors')->nullable();
                $table->string('status', 20)->default('draft')
                    ->comment('draft, sent, accepted, rejected, expired');
                $table->string('quote_reference')->nullable();
                $table->timestamp('valid_until')->nullable();
                $table->json('partner_response')->nullable()
                    ->comment('Réponse brute de l\'API partenaire');
                $table->timestamps();

                $table->index(['residence_id', 'status']);
                $table->index(['owner_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_partner_quotes');
        Schema::dropIfExists('insurance_events');
    }
};
