<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Fonctionnalités Afrique de l'Ouest
     * - Types de location (court terme, colocation, etc.)
     * - Badges de sécurité propriétaires
     * - Analytics avancés
     * - Support multi-devises
     */
    public function up(): void
    {
        // Ajouter devise et symbole aux pays
        if (!Schema::hasColumn('countries', 'currency_symbol')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->string('currency_symbol', 10)->default('FCFA')->after('currency');
                $table->string('currency_name', 50)->default('Franc CFA')->after('currency_symbol');
                $table->string('flag_emoji', 10)->nullable()->after('currency_name');
                $table->string('locale', 10)->default('fr_CI')->after('flag_emoji');
                $table->string('timezone', 50)->default('Africa/Abidjan')->after('locale');
            });
        }

        // Types de location et fonctionnalités immobilier local
        if (!Schema::hasColumn('residences', 'rental_type')) {
            Schema::table('residences', function (Blueprint $table) {
                // Type de location
                $table->enum('rental_type', [
                    'standard',      // Location classique
                    'short_term',    // Court terme / entrée-coucher
                    'colocation',    // Colocation étudiante
                    'corporate',     // Location entreprise
                    'seasonal',       // Saisonnière
                ])->default('standard')->after('type');

                // Avance/Caution négociable
                $table->boolean('deposit_negotiable')->default(false);
                $table->text('deposit_terms')->nullable();

                // Bail
                $table->enum('lease_type', ['written', 'verbal', 'flexible'])->default('written');

                // Cible
                $table->json('target_tenants')->nullable(); // ['students', 'families', 'professionals', 'expatriates']

                // Score de performance
                $table->decimal('performance_score', 5, 2)->default(0);
                $table->decimal('response_rate', 5, 2)->default(0);
                $table->integer('avg_response_time_hours')->nullable();
            });
        }

        // Badges de sécurité pour propriétaires
        Schema::create('owner_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('badge_type'); // verified_identity, verified_phone, verified_residence, superhost, trusted, responsive
            $table->string('badge_name');
            $table->string('badge_icon')->nullable();
            $table->string('badge_color')->default('orange');
            $table->date('earned_at');
            $table->date('expires_at')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'badge_type']);
            $table->index('badge_type');
        });

        // Stats analytics propriétaire (agrégées)
        Schema::create('owner_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            // Métriques de performance
            $table->integer('total_views')->default(0);
            $table->integer('total_inquiries')->default(0);
            $table->integer('total_bookings')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('occupancy_rate', 5, 2)->default(0);
            $table->decimal('avg_booking_value', 12, 2)->default(0);

            // Métriques de réactivité
            $table->integer('messages_received')->default(0);
            $table->integer('messages_answered')->default(0);
            $table->integer('avg_response_time_minutes')->nullable();

            // Métriques de qualité
            $table->decimal('review_score_avg', 3, 2)->nullable();
            $table->integer('reviews_count')->default(0);
            $table->integer('cancellations_count')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index('date');
        });

        // Comparaison prix du marché par zone
        Schema::create('market_price_data', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->default('CI');
            $table->string('city')->nullable();
            $table->string('commune')->nullable();
            $table->string('residence_type'); // apartment, studio, villa, room
            $table->integer('bedrooms')->nullable();

            // Prix du marché
            $table->decimal('avg_price_per_night', 12, 2);
            $table->decimal('min_price_per_night', 12, 2);
            $table->decimal('max_price_per_night', 12, 2);
            $table->decimal('median_price_per_night', 12, 2);

            // Statistiques
            $table->integer('sample_size')->default(0);
            $table->date('period_start');
            $table->date('period_end');

            $table->timestamps();

            $table->index(['country_code', 'city', 'commune']);
            $table->index(['residence_type', 'bedrooms']);
        });

        // WhatsApp messages log
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone_number');
            $table->string('message_type'); // template, text, media
            $table->string('template_name')->nullable();
            $table->text('message_content')->nullable();
            $table->string('status')->default('pending'); // pending, sent, delivered, read, failed
            $table->string('whatsapp_message_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('phone_number');
        });

        // SEO - Structured data cache
        Schema::create('seo_data', function (Blueprint $table) {
            $table->id();
            $table->string('seoable_type');
            $table->unsignedBigInteger('seoable_id');
            $table->string('locale', 5)->default('fr');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('keywords')->nullable();
            $table->json('structured_data')->nullable(); // JSON-LD
            $table->string('canonical_url')->nullable();
            $table->json('og_data')->nullable(); // Open Graph
            $table->timestamps();

            $table->index(['seoable_type', 'seoable_id']);
            $table->unique(['seoable_type', 'seoable_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_data');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('market_price_data');
        Schema::dropIfExists('owner_analytics');
        Schema::dropIfExists('owner_badges');

        if (Schema::hasColumn('residences', 'rental_type')) {
            Schema::table('residences', function (Blueprint $table) {
                $table->dropColumn([
                    'rental_type', 'deposit_negotiable', 'deposit_terms',
                    'lease_type', 'target_tenants', 'performance_score',
                    'response_rate', 'avg_response_time_hours',
                ]);
            });
        }

        if (Schema::hasColumn('countries', 'currency_symbol')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn([
                    'currency_symbol', 'currency_name', 'flag_emoji',
                    'locale', 'timezone',
                ]);
            });
        }
    }
};
