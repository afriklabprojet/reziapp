<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     * Module Paiement adapté Afrique - Jeko API
     */
    public function up(): void
    {
        // Fournisseurs de paiement (Jeko, Orange Money, MTN, Wave, etc.)
        Schema::create('payment_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // jeko, orange_money, mtn_momo, wave, moov_money
            $table->string('name');
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->json('supported_countries')->nullable(); // ['CI', 'SN', 'ML', 'BF']
            $table->json('supported_currencies')->nullable(); // ['XOF', 'XAF']
            $table->decimal('min_amount', 12, 2)->default(100);
            $table->decimal('max_amount', 12, 2)->default(5000000);
            $table->decimal('fee_percentage', 5, 2)->default(0);
            $table->decimal('fee_fixed', 10, 2)->default(0);
            $table->json('api_config')->nullable(); // Encrypted API keys, endpoints
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sandbox')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        // Méthodes de paiement enregistrées par l'utilisateur
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_provider_id')->constrained()->onDelete('cascade');
            $table->string('type'); // mobile_money, card, bank_transfer
            $table->string('label')->nullable(); // "Mon Orange Money"
            $table->string('phone_number')->nullable(); // Pour mobile money
            $table->string('phone_country_code')->default('+225');
            $table->string('card_last_four')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_exp_month')->nullable();
            $table->string('card_exp_year')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number_masked')->nullable();
            $table->string('token')->nullable(); // Token de paiement récurrent
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_default']);
        });

        // Paiements principaux
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_provider_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');

            // Montants
            $table->decimal('amount', 12, 2);
            $table->decimal('fee', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2); // amount + fee
            $table->string('currency', 3)->default('XOF');

            // Type et statut
            $table->enum('type', ['booking', 'deposit', 'extension', 'penalty', 'refund', 'payout'])->default('booking');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded', 'partial_refund'])->default('pending');

            // Références
            $table->string('reference')->unique(); // PAY-2026-000001
            $table->string('provider_reference')->nullable(); // ID chez Jeko
            $table->string('provider_transaction_id')->nullable();

            // Mobile Money spécifique
            $table->string('phone_number')->nullable();
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();

            // Métadonnées
            $table->json('metadata')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('failure_reason')->nullable();

            // Timestamps
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['booking_id']);
            $table->index(['reference']);
            $table->index(['provider_reference']);
            $table->index(['created_at']);
        });

        // Transactions (log détaillé de chaque opération)
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->string('type'); // initiate, otp_sent, otp_verified, processing, success, failure, webhook
            $table->enum('status', ['pending', 'success', 'failed']);
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('XOF');
            $table->string('provider_reference')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'type']);
        });

        // Factures PDF
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');

            // Numérotation
            $table->string('invoice_number')->unique(); // REZI-2026-000001
            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            // Montants
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(18); // TVA 18% CI
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('currency', 3)->default('XOF');

            // Statut
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled', 'refunded'])->default('draft');

            // Client
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone')->nullable();
            $table->text('client_address')->nullable();

            // Vendeur (Propriétaire ou REZI)
            $table->string('seller_name');
            $table->string('seller_email');
            $table->string('seller_phone')->nullable();
            $table->text('seller_address')->nullable();
            $table->string('seller_tax_id')->nullable(); // Numéro fiscal

            // Lignes de facture
            $table->json('line_items'); // [{description, quantity, unit_price, total}]

            // PDF
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['invoice_number']);
            $table->index(['invoice_date']);
        });

        // Virements aux propriétaires (Payouts)
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Propriétaire
            $table->foreignId('payment_provider_id')->nullable()->constrained();

            // Montants
            $table->decimal('gross_amount', 12, 2); // Montant brut
            $table->decimal('platform_fee', 10, 2)->default(0); // Commission REZI
            $table->decimal('transfer_fee', 10, 2)->default(0); // Frais de virement
            $table->decimal('net_amount', 12, 2); // Montant net versé
            $table->string('currency', 3)->default('XOF');

            // Statut
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('reference')->unique(); // PAYOUT-2026-000001
            $table->string('provider_reference')->nullable();

            // Méthode de virement
            $table->string('payout_method'); // mobile_money, bank_transfer
            $table->string('phone_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_iban')->nullable();

            // Période
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            // Réservations incluses
            $table->json('booking_ids')->nullable();

            // Métadonnées
            $table->json('metadata')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['reference']);
        });

        // Solde propriétaire (balance)
        Schema::create('owner_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('available_balance', 12, 2)->default(0); // Disponible pour retrait
            $table->decimal('pending_balance', 12, 2)->default(0); // En attente (réservations non terminées)
            $table->decimal('total_earned', 14, 2)->default(0); // Total gagné
            $table->decimal('total_withdrawn', 14, 2)->default(0); // Total retiré
            $table->string('currency', 3)->default('XOF');
            $table->timestamp('last_payout_at')->nullable();
            $table->timestamps();
        });

        // Seed des fournisseurs de paiement
        $this->seedPaymentProviders();
    }

    /**
     * Seed des fournisseurs de paiement pour l'Afrique de l'Ouest
     */
    private function seedPaymentProviders(): void
    {
        $providers = [
            [
                'code' => 'jeko',
                'name' => 'Jeko Pay',
                'description' => 'Passerelle de paiement africaine - Mobile Money & Cartes',
                'supported_countries' => json_encode(['CI', 'SN', 'ML', 'BF', 'TG', 'BJ', 'NE', 'GN']),
                'supported_currencies' => json_encode(['XOF', 'XAF', 'GNF']),
                'min_amount' => 100,
                'max_amount' => 5000000,
                'fee_percentage' => 2.5,
                'fee_fixed' => 0,
                'is_active' => true,
                'display_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'orange_money',
                'name' => 'Orange Money',
                'description' => 'Paiement mobile Orange Money Côte d\'Ivoire',
                'supported_countries' => json_encode(['CI', 'SN', 'ML', 'BF', 'GN', 'CM']),
                'supported_currencies' => json_encode(['XOF', 'XAF', 'GNF']),
                'min_amount' => 100,
                'max_amount' => 2000000,
                'fee_percentage' => 1.5,
                'fee_fixed' => 100,
                'is_active' => true,
                'display_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'mtn_momo',
                'name' => 'MTN Mobile Money',
                'description' => 'Paiement mobile MTN MoMo',
                'supported_countries' => json_encode(['CI', 'GH', 'CM', 'UG', 'RW']),
                'supported_currencies' => json_encode(['XOF', 'XAF', 'GHS', 'UGX', 'RWF']),
                'min_amount' => 100,
                'max_amount' => 2000000,
                'fee_percentage' => 1.5,
                'fee_fixed' => 100,
                'is_active' => true,
                'display_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'wave',
                'name' => 'Wave',
                'description' => 'Paiement mobile Wave - Sans frais',
                'supported_countries' => json_encode(['CI', 'SN', 'ML', 'BF']),
                'supported_currencies' => json_encode(['XOF']),
                'min_amount' => 100,
                'max_amount' => 1500000,
                'fee_percentage' => 1,
                'fee_fixed' => 0,
                'is_active' => true,
                'display_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'moov_money',
                'name' => 'Moov Money',
                'description' => 'Paiement mobile Moov Money',
                'supported_countries' => json_encode(['CI', 'BF', 'TG', 'BJ', 'NE']),
                'supported_currencies' => json_encode(['XOF']),
                'min_amount' => 100,
                'max_amount' => 1000000,
                'fee_percentage' => 1.5,
                'fee_fixed' => 50,
                'is_active' => true,
                'display_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'bank_transfer',
                'name' => 'Virement Bancaire',
                'description' => 'Virement bancaire UEMOA',
                'supported_countries' => json_encode(['CI', 'SN', 'ML', 'BF', 'TG', 'BJ', 'NE', 'GN']),
                'supported_currencies' => json_encode(['XOF']),
                'min_amount' => 10000,
                'max_amount' => 50000000,
                'fee_percentage' => 0,
                'fee_fixed' => 2500,
                'is_active' => true,
                'display_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        \DB::table('payment_providers')->insert($providers);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_balances');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('payment_providers');
    }
};
