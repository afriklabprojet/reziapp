<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_deposits', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // REF-SD-2026-XXXX
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lease_contract_id')->nullable()->constrained()->nullOnDelete();

            // Montant
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('XOF');

            // Statut
            $table->enum('status', [
                'pending',       // En attente de paiement
                'held',          // Retenu par la plateforme
                'partial_return',// Retour partiel
                'returned',      // Retourné intégralement au locataire
                'forfeited',     // Retenu par le propriétaire (dommages)
                'disputed',      // En litige
            ])->default('pending');

            // Paiement de la caution
            $table->string('payment_method')->nullable(); // mobile_money, bank, cash
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Restitution
            $table->decimal('returned_amount', 12, 2)->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('return_payment_method')->nullable();
            $table->string('return_reference')->nullable();
            $table->text('deduction_reasons')->nullable(); // Raisons de retenue partielle
            $table->json('deduction_items')->nullable(); // [{item: 'Réparation', amount: 5000}]

            // Date limite de restitution (légal CI: 30 jours)
            $table->date('return_deadline')->nullable();

            // Preuve de paiement
            $table->string('receipt_path')->nullable();

            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_deposits');
    }
};
