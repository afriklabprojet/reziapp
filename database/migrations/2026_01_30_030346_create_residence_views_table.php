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
        Schema::create('residence_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');

            // Informations de visite
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();

            // Source de la visite
            $table->string('source')->default('direct'); // search, map, recommendation, direct

            // Durée de visite (mise à jour en JS)
            $table->integer('duration_seconds')->nullable();

            // Interactions
            $table->boolean('contacted')->default(false);
            $table->boolean('favorited')->default(false);
            $table->boolean('shared')->default(false);

            $table->timestamps();

            // Index
            $table->index(['user_id', 'created_at']);
            $table->index(['residence_id', 'created_at']);
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residence_views');
    }
};
