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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->tinyInteger('cleanliness_rating')->unsigned()->nullable(); // Propreté
            $table->tinyInteger('location_rating')->unsigned()->nullable(); // Emplacement
            $table->tinyInteger('value_rating')->unsigned()->nullable(); // Rapport qualité/prix
            $table->tinyInteger('communication_rating')->unsigned()->nullable(); // Communication
            $table->text('comment');
            $table->text('owner_response')->nullable();
            $table->timestamp('owner_response_at')->nullable();
            $table->text('owner_review_for_guest')->nullable(); // Avis du propriétaire sur le voyageur
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->unique(['residence_id', 'user_id']); // Un avis par utilisateur par résidence
            $table->index(['residence_id', 'status', 'rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
