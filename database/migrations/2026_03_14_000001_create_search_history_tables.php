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
        if (!Schema::hasTable('search_histories')) {
            Schema::create('search_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');

                // Search parameters
                $table->string('query')->nullable(); // Text search
                $table->foreignId('city_id')->nullable()->constrained();
                $table->foreignId('commune_id')->nullable()->constrained();
                $table->foreignId('residence_type_id')->nullable()->constrained();

                // Filters
                $table->decimal('min_price', 10, 2)->nullable();
                $table->decimal('max_price', 10, 2)->nullable();
                $table->integer('bedrooms')->nullable();
                $table->integer('bathrooms')->nullable();
                $table->integer('guests')->nullable();
                $table->json('amenities')->nullable(); // Array of amenity IDs

                // Location
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->integer('radius_km')->nullable();

                // Dates
                $table->date('check_in')->nullable();
                $table->date('check_out')->nullable();

                // Results
                $table->integer('results_count')->default(0);

                // Metadata
                $table->string('device_type')->nullable(); // mobile, desktop, tablet
                $table->string('source')->nullable(); // homepage, map, direct

                $table->timestamp('searched_at');
                $table->timestamps();

                // Indexes
                $table->index(['user_id', 'searched_at']);
                $table->index('searched_at');
            });
        }

        // Price alerts table
        if (!Schema::hasTable('price_alerts')) {
            Schema::create('price_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('search_history_id')->nullable()->constrained()->onDelete('set null');

                // Alert criteria
                $table->string('name')->nullable();
                $table->foreignId('city_id')->nullable()->constrained();
                $table->foreignId('commune_id')->nullable()->constrained();
                $table->foreignId('residence_type_id')->nullable()->constrained();
                $table->decimal('max_price', 10, 2);
                $table->integer('min_bedrooms')->nullable();
                $table->json('amenities')->nullable();

                // Alert settings
                $table->enum('frequency', ['instant', 'daily', 'weekly'])->default('daily');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_triggered_at')->nullable();
                $table->integer('matches_count')->default(0);

                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'is_active']);
            });
        }

        // Viewed residences (for "recently viewed")
        if (!Schema::hasTable('viewed_residences')) {
            Schema::create('viewed_residences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('residence_id')->constrained()->onDelete('cascade');
                $table->integer('view_count')->default(1);
                $table->integer('duration_seconds')->nullable();
                $table->timestamp('last_viewed_at');
                $table->timestamps();

                $table->unique(['user_id', 'residence_id']);
                $table->index(['user_id', 'last_viewed_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viewed_residences');
        Schema::dropIfExists('price_alerts');
        Schema::dropIfExists('search_histories');
    }
};
