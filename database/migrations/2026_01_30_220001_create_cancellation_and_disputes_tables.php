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
        // Politiques d'annulation
        Schema::create('cancellation_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // flexible, moderate, strict, super_strict
            $table->string('display_name'); // Nom affiché
            $table->text('description');

            // Règles de remboursement (en JSON pour flexibilité)
            // Format: [{"days_before": 7, "refund_percent": 100}, {"days_before": 1, "refund_percent": 50}]
            $table->json('refund_rules');

            // Frais de service non remboursables (%)
            $table->decimal('service_fee_refundable_percent', 5, 2)->default(0);

            // Remboursement si annulation par propriétaire
            $table->decimal('owner_cancellation_refund_percent', 5, 2)->default(100);

            // Pénalité propriétaire en cas d'annulation (% du montant)
            $table->decimal('owner_cancellation_penalty_percent', 5, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });

        // Table des réservations
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // REF-XXXXXX

            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Le voyageur
            $table->foreignId('cancellation_policy_id')->constrained();

            // Dates
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('nights');
            $table->integer('guests')->default(1);

            // Montants
            $table->decimal('price_per_night', 10, 2);
            $table->decimal('subtotal', 10, 2); // prix x nuits
            $table->decimal('cleaning_fee', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0); // Commission ReziApp
            $table->decimal('taxes', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->decimal('total_amount', 10, 2);

            // Paiement
            $table->string('payment_status')->default('pending'); // pending, paid, partially_refunded, refunded, failed
            $table->string('payment_method')->nullable(); // card, mobile_money, bank_transfer
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Statut réservation
            $table->string('status')->default('pending');
            // pending, confirmed, cancelled_by_user, cancelled_by_owner, completed, no_show, disputed

            // Infos annulation
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by')->nullable(); // user, owner, admin, system
            $table->text('cancellation_reason')->nullable();

            // Messages
            $table->text('guest_message')->nullable();
            $table->text('owner_notes')->nullable();

            // Confirmation
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('owner_response_deadline')->nullable();

            // Check-in/out effectifs
            $table->timestamp('actual_check_in')->nullable();
            $table->timestamp('actual_check_out')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['user_id', 'status']);
            $table->index(['residence_id', 'status']);
            $table->index(['check_in', 'check_out']);
            $table->index('status');
            $table->index('payment_status');
        });

        // Table des annulations
        Schema::create('cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');

            $table->string('initiated_by'); // user, owner, admin, system
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users');

            $table->string('reason_category'); // plans_changed, found_alternative, emergency, property_issue, host_issue, other
            $table->text('reason_details')->nullable();

            // Calcul remboursement
            $table->integer('days_before_checkin');
            $table->decimal('refund_percent_applied', 5, 2);
            $table->decimal('original_amount', 10, 2);
            $table->decimal('refund_amount', 10, 2);
            $table->decimal('penalty_amount', 10, 2)->default(0);
            $table->decimal('service_fee_refunded', 10, 2)->default(0);

            // Statut
            $table->string('status')->default('pending'); // pending, approved, processed, rejected

            // Pour annulations propriétaire
            $table->boolean('owner_penalty_applied')->default(false);
            $table->decimal('owner_penalty_amount', 10, 2)->default(0);

            // Admin review
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('initiated_by');
        });

        // Table des remboursements
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // RFD-XXXXXX

            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('cancellation_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained(); // Bénéficiaire

            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('XOF');

            $table->string('type'); // cancellation, partial, dispute_resolution, goodwill, error_correction
            $table->text('reason')->nullable();

            // Méthode de remboursement
            $table->string('refund_method'); // original_payment, mobile_money, bank_transfer, credit
            $table->json('refund_details')->nullable(); // Détails selon méthode

            // Statut
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->timestamp('processed_at')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->text('failure_reason')->nullable();

            // Traitement
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->text('admin_notes')->nullable();

            // Automatique ou manuel
            $table->boolean('is_automatic')->default(true);

            $table->timestamps();

            $table->index('status');
            $table->index(['user_id', 'status']);
        });

        // Table des litiges
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // DSP-XXXXXX

            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('opened_by')->constrained('users'); // Qui a ouvert le litige
            $table->foreignId('against_user_id')->constrained('users'); // Contre qui

            $table->string('category'); // property_not_as_described, cleanliness, safety, host_behavior, guest_behavior, payment, damage, other
            $table->string('priority')->default('medium'); // low, medium, high, urgent

            $table->string('title');
            $table->text('description');
            $table->json('evidence_files')->nullable(); // Photos, documents

            // Montant réclamé
            $table->decimal('claimed_amount', 10, 2)->nullable();
            $table->text('claim_justification')->nullable();

            // Statut
            $table->string('status')->default('open');
            // open, under_review, awaiting_response, mediation, resolved, closed, escalated

            // Réponse de l'autre partie
            $table->text('response')->nullable();
            $table->json('response_evidence')->nullable();
            $table->timestamp('responded_at')->nullable();

            // Résolution
            $table->string('resolution_type')->nullable(); // refund_full, refund_partial, no_refund, mutual_agreement, favor_guest, favor_host
            $table->text('resolution_details')->nullable();
            $table->decimal('resolution_amount', 10, 2)->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Assignation
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('assigned_at')->nullable();

            // Deadlines
            $table->timestamp('response_deadline')->nullable();
            $table->timestamp('resolution_deadline')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index(['opened_by', 'status']);
            $table->index('assigned_to');
        });

        // Messages du litige
        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();

            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_internal')->default(false); // Notes internes admin
            $table->boolean('is_system')->default(false); // Messages système

            $table->timestamps();

            $table->index(['dispute_id', 'created_at']);
        });

        // Tickets de support
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // TKT-XXXXXX

            $table->foreignId('user_id')->constrained();
            $table->foreignId('booking_id')->nullable()->constrained();
            $table->foreignId('residence_id')->nullable()->constrained();

            $table->string('category'); // booking, payment, cancellation, account, technical, complaint, suggestion, other
            $table->string('priority')->default('medium'); // low, medium, high, urgent

            $table->string('subject');
            $table->text('description');
            $table->json('attachments')->nullable();

            // Statut
            $table->string('status')->default('open'); // open, in_progress, awaiting_customer, awaiting_internal, resolved, closed

            // Assignation
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('assigned_at')->nullable();

            // Résolution
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');

            // Satisfaction
            $table->tinyInteger('satisfaction_rating')->nullable(); // 1-5
            $table->text('satisfaction_feedback')->nullable();

            // SLA
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('sla_deadline')->nullable();
            $table->boolean('sla_breached')->default(false);

            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index(['user_id', 'status']);
            $table->index('assigned_to');
        });

        // Messages du ticket
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();

            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_internal')->default(false); // Notes internes
            $table->boolean('is_auto_reply')->default(false);

            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['support_ticket_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('dispute_messages');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('cancellations');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('cancellation_policies');
    }
};
