<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Table des demandes de contact entre utilisateurs et propriétaires
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');

            // Informations de contact
            $table->string('phone')->nullable();
            $table->text('message')->nullable();

            // Statut de la demande
            $table->enum('status', ['pending', 'viewed', 'responded', 'closed'])->default('pending');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();

            // Géolocalisation au moment du contact
            $table->decimal('user_latitude', 10, 8)->nullable();
            $table->decimal('user_longitude', 11, 8)->nullable();

            $table->timestamps();

            // Index pour les requêtes fréquentes
            $table->index(['owner_id', 'status']);
            $table->index(['residence_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
