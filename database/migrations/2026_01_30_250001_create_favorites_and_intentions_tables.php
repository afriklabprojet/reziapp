<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Collections table - CREATE FIRST (before favorites extension)
        if (!Schema::hasTable('collections')) {
            Schema::create('collections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('cover_image')->nullable();
                $table->boolean('is_public')->default(false);
                $table->string('share_token')->nullable()->unique();
                $table->integer('favorites_count')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }

        // Extend existing favorites table (after collections is created)
        if (Schema::hasTable('favorites') && !Schema::hasColumn('favorites', 'collection_id')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->foreignId('collection_id')->nullable()->after('residence_id')->constrained()->nullOnDelete();
                $table->json('tags')->nullable()->after('notes');
            });
        }

        // View history table
        if (!Schema::hasTable('view_history')) {
            Schema::create('view_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
                $table->integer('view_count')->default(1);
                $table->integer('total_duration_seconds')->default(0);
                $table->timestamp('first_viewed_at');
                $table->timestamp('last_viewed_at');
                $table->json('view_sessions')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'residence_id']);
                $table->index(['user_id', 'last_viewed_at']);
            });
        }

        // Price alerts table
        if (!Schema::hasTable('price_alerts')) {
            Schema::create('price_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
                $table->decimal('original_price', 10, 2);
                $table->decimal('target_price', 10, 2)->nullable();
                $table->decimal('current_price', 10, 2);
                $table->decimal('price_change', 10, 2)->default(0);
                $table->enum('alert_type', ['any_change', 'decrease_only', 'target_reached'])->default('decrease_only');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_notified_at')->nullable();
                $table->integer('notification_count')->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'residence_id']);
                $table->index(['is_active', 'updated_at']);
            });
        }

        // Saved searches table
        if (!Schema::hasTable('saved_searches')) {
            Schema::create('saved_searches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->json('filters');
                $table->string('location')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->integer('radius_km')->nullable();
                $table->decimal('min_price', 10, 2)->nullable();
                $table->decimal('max_price', 10, 2)->nullable();
                $table->date('check_in')->nullable();
                $table->date('check_out')->nullable();
                $table->integer('guests')->nullable();
                $table->boolean('has_alerts')->default(false);
                $table->enum('alert_frequency', ['instant', 'daily', 'weekly'])->default('daily');
                $table->integer('new_results_count')->default(0);
                $table->timestamp('last_searched_at')->nullable();
                $table->timestamp('last_alert_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'has_alerts']);
                $table->index(['has_alerts', 'alert_frequency']);
            });
        }

        // Property shares tracking
        if (!Schema::hasTable('property_shares')) {
            Schema::create('property_shares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
                $table->enum('platform', ['whatsapp', 'facebook', 'twitter', 'email', 'link', 'sms'])->default('link');
                $table->string('share_token')->unique();
                $table->integer('click_count')->default(0);
                $table->integer('booking_count')->default(0);
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();

                $table->index(['residence_id', 'platform']);
                $table->index(['share_token']);
            });
        }

        // Comparison lists for comparing properties
        if (!Schema::hasTable('comparison_lists')) {
            Schema::create('comparison_lists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name')->default('Ma comparaison');
                $table->json('residence_ids');
                $table->string('share_token')->nullable()->unique();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        // Remove extended columns from favorites
        if (Schema::hasColumn('favorites', 'collection_id')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->dropForeign(['collection_id']);
                $table->dropColumn(['collection_id', 'notes', 'tags']);
            });
        }

        Schema::dropIfExists('comparison_lists');
        Schema::dropIfExists('property_shares');
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('price_alerts');
        Schema::dropIfExists('view_history');
        Schema::dropIfExists('collections');
    }
};
