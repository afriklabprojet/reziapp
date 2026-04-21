<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('lease_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // REF-LC-2026-XXXX
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            // Informations contractuelles
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('lease_type', ['short_term', 'monthly', 'fixed_term'])->default('short_term');
            $table->decimal('monthly_rent', 12, 2);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('XOF');
            $table->unsignedTinyInteger('payment_day')->default(1); // Jour du mois pour paiement

            // Clauses spéciales
            $table->text('special_clauses')->nullable();
            $table->json('included_services')->nullable(); // ['électricité', 'eau', 'wifi']

            // Statut et signatures
            $table->enum('status', [
                'draft',
                'pending_tenant',
                'pending_owner',
                'active',
                'terminated',
                'expired',
            ])->default('draft');

            $table->timestamp('owner_signed_at')->nullable();
            $table->timestamp('tenant_signed_at')->nullable();
            $table->string('owner_signature_ip', 45)->nullable();
            $table->string('tenant_signature_ip', 45)->nullable();

            // PDF généré
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            // Résiliation
            $table->date('terminated_at')->nullable();
            $table->text('termination_reason')->nullable();
            $table->enum('terminated_by', ['owner', 'tenant', 'system'])->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index(['residence_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_contracts');
    }
};
