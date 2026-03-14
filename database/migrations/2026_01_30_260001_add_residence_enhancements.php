<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Enhance residences table
        Schema::table('residences', function (Blueprint $table) {
            // Capacity
            if (!Schema::hasColumn('residences', 'max_guests')) {
                $table->integer('max_guests')->default(2)->after('bathrooms');
            }

            // House rules
            if (!Schema::hasColumn('residences', 'house_rules')) {
                $table->json('house_rules')->nullable()->after('description');
            }

            // Check-in/out times
            if (!Schema::hasColumn('residences', 'check_in_time')) {
                $table->time('check_in_time')->nullable()->after('max_guests');
            }
            if (!Schema::hasColumn('residences', 'check_out_time')) {
                $table->time('check_out_time')->nullable()->after('check_in_time');
            }

            // Virtual tour
            if (!Schema::hasColumn('residences', 'virtual_tour_url')) {
                $table->string('virtual_tour_url')->nullable()->after('house_rules');
            }

            // Badges
            if (!Schema::hasColumn('residences', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('status');
            }
            if (!Schema::hasColumn('residences', 'is_top_residence')) {
                $table->boolean('is_top_residence')->default(false)->after('is_verified');
            }
            if (!Schema::hasColumn('residences', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('is_top_residence');
            }

            // Reviews summary
            if (!Schema::hasColumn('residences', 'average_rating')) {
                $table->decimal('average_rating', 2, 1)->nullable()->after('views_count');
            }
            if (!Schema::hasColumn('residences', 'reviews_count')) {
                $table->integer('reviews_count')->default(0)->after('average_rating');
            }
        });

        // Create photos_360 table for 360° photos
        if (!Schema::hasTable('photos_360')) {
            Schema::create('photos_360', function (Blueprint $table) {
                $table->id();
                $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
                $table->string('path');
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->index(['residence_id', 'order']);
            });
        }

        // Create residence_verification_badges table
        if (!Schema::hasTable('residence_badges')) {
            Schema::create('residence_badges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
                $table->enum('badge_type', [
                    'verified',           // Résidence vérifiée (visite effectuée)
                    'top_residence',      // Top résidence (note > 4.5)
                    'superhost',          // Propriétaire superhost
                    'instant_booking',    // Réservation instantanée
                    'new_listing',        // Nouvelle annonce
                    'responsive_host',    // Hôte réactif
                    'eco_friendly',       // Éco-responsable
                    'family_friendly',    // Adapté aux familles
                    'business_ready',     // Prêt pour les affaires
                ]);
                $table->timestamp('earned_at');
                $table->timestamp('expires_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['residence_id', 'badge_type']);
            });
        }

        // Points of interest (nearby places)
        if (!Schema::hasTable('points_of_interest')) {
            Schema::create('points_of_interest', function (Blueprint $table) {
                $table->id();
                $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->enum('type', [
                    'restaurant',
                    'supermarket',
                    'pharmacy',
                    'hospital',
                    'bank',
                    'transport',
                    'beach',
                    'mall',
                    'school',
                    'mosque',
                    'church',
                    'park',
                    'gym',
                    'other',
                ]);
                $table->decimal('distance_meters', 10, 2);
                $table->integer('walking_time_minutes')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->timestamps();

                $table->index(['residence_id', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('points_of_interest');
        Schema::dropIfExists('residence_badges');
        Schema::dropIfExists('photos_360');

        Schema::table('residences', function (Blueprint $table) {
            $columns = [
                'max_guests', 'house_rules', 'check_in_time', 'check_out_time',
                'virtual_tour_url', 'is_verified', 'is_top_residence', 'verified_at',
                'average_rating', 'reviews_count',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('residences', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
