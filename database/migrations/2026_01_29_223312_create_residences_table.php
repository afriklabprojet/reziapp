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
        Schema::create('residences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            $table->string('address');
            $table->string('commune');
            $table->string('quartier');

            // Geolocation fields
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);

            // Pricing
            $table->decimal('price_per_day', 10, 2)->nullable();
            $table->decimal('price_per_week', 10, 2)->nullable();
            $table->decimal('price_per_month', 10, 2);

            // Status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_available')->default(true);

            // House rules
            $table->boolean('pets_allowed')->default(false);
            $table->boolean('smoking_allowed')->default(false);
            $table->boolean('parties_allowed')->default(false);
            $table->integer('floor')->nullable();
            $table->boolean('has_elevator')->default(false);

            // Statistics
            $table->integer('views_count')->default(0);
            $table->integer('contacts_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Spatial index for geolocation queries
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residences');
    }
};
