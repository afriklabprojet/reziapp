<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table principale des états des lieux
        Schema::create('property_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // EDL-2026-XXXX
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lease_contract_id')->nullable()->constrained()->nullOnDelete();

            // Type et statut
            $table->enum('type', ['check_in', 'check_out', 'periodic'])->default('check_in');
            $table->enum('status', ['draft', 'in_progress', 'completed', 'signed'])->default('draft');

            // Date et acteurs
            $table->dateTime('inspection_date');
            $table->string('inspector_name')->nullable(); // Nom de l'inspecteur si différent du propriétaire
            $table->boolean('tenant_present')->default(false);

            // Compteurs (eau, électricité, gaz)
            $table->decimal('electricity_meter', 10, 2)->nullable();
            $table->decimal('water_meter', 10, 2)->nullable();
            $table->decimal('gas_meter', 10, 2)->nullable();

            // Remise des clés
            $table->integer('keys_count')->nullable();
            $table->integer('keys_returned')->nullable();
            $table->integer('badges_count')->nullable();
            $table->integer('badges_returned')->nullable();

            // Observations globales et suite à donner
            $table->text('global_observations')->nullable();
            $table->decimal('estimated_repair_cost', 12, 2)->nullable();

            // Signature
            $table->timestamp('owner_signed_at')->nullable();
            $table->timestamp('tenant_signed_at')->nullable();
            $table->string('owner_signature_ip', 45)->nullable();
            $table->string('tenant_signature_ip', 45)->nullable();

            // PDF
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['owner_id', 'type', 'status']);
            $table->index(['residence_id', 'type']);
        });

        // Table des pièces/éléments inspectés
        Schema::create('inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_inspection_id')->constrained()->cascadeOnDelete();

            // Pièce et élément
            $table->string('room');     // salon, chambre 1, cuisine, salle de bain...
            $table->string('element');  // sol, murs, plafond, fenêtre, porte, équipement...

            // État: bon / passable / mauvais / à remplacer
            $table->enum('condition', ['good', 'fair', 'damaged', 'missing'])->default('good');

            $table->text('observations')->nullable();
            $table->json('photos')->nullable(); // Tableau de chemins d'images
            $table->decimal('repair_estimate', 10, 2)->nullable();
            $table->boolean('requires_action')->default(false);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['property_inspection_id', 'room']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_items');
        Schema::dropIfExists('property_inspections');
    }
};
