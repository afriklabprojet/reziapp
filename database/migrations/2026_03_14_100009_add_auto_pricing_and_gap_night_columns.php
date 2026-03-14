<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            // Yield Management automatique
            $table->boolean('auto_pricing_enabled')->default(false)->after('instant_book');
            $table->decimal('auto_pricing_min', 10, 0)->nullable()->after('auto_pricing_enabled');
            $table->decimal('auto_pricing_max', 10, 0)->nullable()->after('auto_pricing_min');

            // Gap-night pricing
            $table->boolean('gap_night_pricing_enabled')->default(false)->after('auto_pricing_max');
            $table->integer('gap_night_discount_percent')->default(20)->after('gap_night_pricing_enabled');
            $table->integer('gap_night_max_days')->default(2)->after('gap_night_discount_percent');

            // Onboarding
            $table->integer('listing_quality_score')->nullable()->after('reviews_count');
        });

        // Onboarding progression pour les propriétaires
        Schema::table('users', function (Blueprint $table) {
            $table->json('onboarding_steps')->nullable()->after('remember_token');
            $table->boolean('onboarding_completed')->default(false)->after('onboarding_steps');
            $table->string('preferred_language')->default('fr')->after('onboarding_completed');
        });
    }

    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn([
                'auto_pricing_enabled',
                'auto_pricing_min',
                'auto_pricing_max',
                'gap_night_pricing_enabled',
                'gap_night_discount_percent',
                'gap_night_max_days',
                'listing_quality_score',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'onboarding_steps',
                'onboarding_completed',
                'preferred_language',
            ]);
        });
    }
};
