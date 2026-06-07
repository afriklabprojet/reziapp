<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds missing indexes identified by auditing high-traffic query patterns.
 *
 * Methodology: examined all existing indexes via Schema::getIndexes(), cross-referenced
 * against query patterns in models (scopes), controllers, and services.
 *
 * Tables covered: residences, payments, referrals, reviews, sponsored_listings,
 *                 bookings, coupons, booking_insurances, search_history.
 */
return new class () extends Migration {
    public function up(): void
    {
        // ── residences ────────────────────────────────────────────────────────
        // owner dashboard: WHERE owner_id = ? AND status IN ('approved','active')
        // Only residences_owner_id_foreign (single-column FK) exists; no composite.
        Schema::table('residences', function (Blueprint $table) {
            $table->index(['owner_id', 'status', 'is_available'], 'idx_residences_owner_status_available');
        });

        // ── payments ──────────────────────────────────────────────────────────
        // Admin & analytics filter: WHERE type = ? AND status = ?
        // Also used by MarketingAnalyticsService and payment history pages.
        // Existing: user_id+status. Missing: type+status composite.
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['type', 'status'], 'idx_payments_type_status');
            // completed_at for revenue reporting and time-window queries
            $table->index(['completed_at'], 'idx_payments_completed_at');
        });

        // ── referrals ─────────────────────────────────────────────────────────
        // MarketingAnalyticsService: WHERE status = 'qualified', WHERE status = 'rewarded'
        // Referral::where('status','pending')->count() etc.
        // Existing: only unique(referrer_id, referred_id) + referred_id FK.
        // referrer_id+status covers "my referrals by status" queries.
        // referred_id+status covers "was this user referred and status?" lookups.
        Schema::table('referrals', function (Blueprint $table) {
            $table->index(['referrer_id', 'status'], 'idx_referrals_referrer_status');
            $table->index(['referred_id', 'status'], 'idx_referrals_referred_status');
            $table->index(['status'], 'idx_referrals_status');
        });

        // ── reviews ──────────────────────────────────────────────────────────
        // enhance_reviews added booking_id column but never indexed it.
        // Booking::review() hasOne uses this FK for lookups and existence checks.
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['booking_id'], 'idx_reviews_booking_id');
        });

        // ── sponsored_listings ───────────────────────────────────────────────
        // Owner dashboard: WHERE user_id = ? AND status = ?
        // MarketingAnalyticsService: WHERE status = 'active', WHERE is_paid = true
        // Existing: user_id FK (single column), status, type+status.
        // Missing: user_id+status composite; is_paid+status for revenue queries.
        Schema::table('sponsored_listings', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_sponsored_listings_user_status');
            $table->index(['is_paid', 'status'], 'idx_sponsored_listings_paid_status');
        });

        // ── bookings ─────────────────────────────────────────────────────────
        // completed_at used in owner analytics and revenue rollup queries.
        // Existing indexes already cover: user_id+status, residence_id+status,
        // check_in+check_out, status, payment_status, and the 4-column availability index.
        // Missing: completed_at for period-based aggregations.
        // Also: payment_status is indexed alone; adding user_id+payment_status covers
        // "my payments" listing pages with a range filter.
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['completed_at'], 'idx_bookings_completed_at');
            $table->index(['user_id', 'payment_status'], 'idx_bookings_user_payment_status');
        });

        // ── coupons ──────────────────────────────────────────────────────────
        // Coupon validation: WHERE code = ? AND is_active = true
        // Existing: code unique + code index (both single-column), starts_at+expires_at.
        // A covering composite (code, is_active) avoids a second lookup after the unique scan.
        // Guard: coupon_uses already has this covered; skip if already present.
        $couponIndexNames = collect(Schema::getIndexes('coupons'))->pluck('name')->toArray();
        if (! in_array('idx_coupons_code_active', $couponIndexNames)) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->index(['code', 'is_active'], 'idx_coupons_code_active');
            });
        }

        // ── booking_insurances ───────────────────────────────────────────────
        // RiskScoringService queries claims via booking → residence → owner_id.
        // user_id FK has no index (only FK constraint defined); needed for:
        // WHERE user_id = ? (insurance history page).
        Schema::table('booking_insurances', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_booking_insurances_user_status');
        });

        // ── search_history ───────────────────────────────────────────────────
        // Table exists (created by create_search_history_tables) but has no indexes.
        // Common pattern: WHERE user_id = ? ORDER BY created_at DESC.
        if (Schema::hasTable('search_history') && Schema::hasColumn('search_history', 'user_id')) {
            $shIndexes = collect(Schema::getIndexes('search_history'))->pluck('name')->toArray();
            if (! in_array('idx_search_history_user_created', $shIndexes)) {
                Schema::table('search_history', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at'], 'idx_search_history_user_created');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropIndex('idx_residences_owner_status_available');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_type_status');
            $table->dropIndex('idx_payments_completed_at');
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropIndex('idx_referrals_referrer_status');
            $table->dropIndex('idx_referrals_referred_status');
            $table->dropIndex('idx_referrals_status');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_booking_id');
        });

        Schema::table('sponsored_listings', function (Blueprint $table) {
            $table->dropIndex('idx_sponsored_listings_user_status');
            $table->dropIndex('idx_sponsored_listings_paid_status');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_completed_at');
            $table->dropIndex('idx_bookings_user_payment_status');
        });

        $couponIndexNames = collect(Schema::getIndexes('coupons'))->pluck('name')->toArray();
        if (in_array('idx_coupons_code_active', $couponIndexNames)) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->dropIndex('idx_coupons_code_active');
            });
        }

        Schema::table('booking_insurances', function (Blueprint $table) {
            $table->dropIndex('idx_booking_insurances_user_status');
        });

        if (Schema::hasTable('search_history') && Schema::hasColumn('search_history', 'user_id')) {
            $shIndexes = collect(Schema::getIndexes('search_history'))->pluck('name')->toArray();
            if (in_array('idx_search_history_user_created', $shIndexes)) {
                Schema::table('search_history', function (Blueprint $table) {
                    $table->dropIndex('idx_search_history_user_created');
                });
            }
        }
    }
};
