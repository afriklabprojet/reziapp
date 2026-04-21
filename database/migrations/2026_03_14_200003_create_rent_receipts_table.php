<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('rent_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // QUITT-2026-XXXX
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lease_contract_id')->nullable()->constrained()->nullOnDelete();

            // Période couverte
            $table->date('period_start');
            $table->date('period_end');

            // Montants
            $table->decimal('rent_amount', 12, 2);
            $table->decimal('charges_amount', 12, 2)->default(0); // Charges: eau, élec, etc.
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('XOF');

            // Paiement
            $table->string('payment_method')->nullable(); // mobile_money, bank, cash
            $table->string('payment_reference')->nullable();
            $table->date('payment_date');
            $table->boolean('is_paid')->default(true);

            // PDF
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            // Envoi
            $table->boolean('sent_by_email')->default(false);
            $table->boolean('sent_by_whatsapp')->default(false);
            $table->timestamp('sent_at')->nullable();

            // Détails charges (JSON)
            $table->json('charges_detail')->nullable(); // [{label: 'Eau', amount: 2000}]

            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['owner_id', 'period_start']);
            $table->index(['tenant_id', 'period_start']);
            $table->index(['residence_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_receipts');
    }
};
