<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table des services additionnels disponibles
        Schema::create('additional_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                  // Nom du service
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();                      // Icône (heroicon ou classe)
            $table->enum('pricing_type', ['fixed', 'per_night', 'per_guest', 'per_item']);
            $table->decimal('price', 10, 2);                         // Prix
            $table->boolean('requires_quantity')->default(false);    // Si une quantité est requise
            $table->integer('max_quantity')->nullable();             // Quantité max
            $table->string('category')->nullable();                  // Catégorie (transport, food, experience)
            $table->boolean('requires_advance_booking')->default(false); // Réservation à l'avance requise
            $table->integer('advance_hours')->nullable();            // Heures à l'avance min
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        // Services disponibles par résidence
        Schema::create('residence_additional_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('additional_service_id')->constrained()->cascadeOnDelete();
            $table->decimal('custom_price', 10, 2)->nullable();      // Prix personnalisé (null = prix par défaut)
            $table->boolean('is_available')->default(true);
            $table->text('custom_description')->nullable();
            $table->timestamps();

            $table->unique(['residence_id', 'additional_service_id'], 'res_add_srv_unique');
        });

        // Services commandés avec une réservation
        Schema::create('booking_additional_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('additional_service_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('residence_additional_service_id')->nullable();
            $table->foreign('residence_additional_service_id', 'bk_add_srv_res_fk')
                  ->references('id')
                  ->on('residence_additional_services')
                  ->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'delivered', 'cancelled'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();           // Date/heure prévue
            $table->text('notes')->nullable();                       // Notes du client
            $table->text('provider_notes')->nullable();              // Notes du fournisseur
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_additional_services');
        Schema::dropIfExists('residence_additional_services');
        Schema::dropIfExists('additional_services');
    }
};
