<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reviews bilatérales aveugles (double-blind) — pattern Airbnb.
 *
 * Une review n'est révélée publiquement que si :
 *   1) L'autre partie a aussi soumis sa review, OU
 *   2) 14 jours se sont écoulés depuis la fin du séjour.
 *
 * `published_at` = NULL → pas encore visible publiquement.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('reviews') && !Schema::hasColumn('reviews', 'published_at')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->timestamp('published_at')->nullable()->after('status');
                $table->index(['status', 'published_at']);
            });

            // Backfill : tous les reviews approuvés existants sont publiés (legacy)
            DB::table('reviews')
                ->where('status', 'approved')
                ->whereNull('published_at')
                ->update(['published_at' => DB::raw('updated_at')]);
        }

        if (Schema::hasTable('tenant_reviews') && !Schema::hasColumn('tenant_reviews', 'published_at')) {
            Schema::table('tenant_reviews', function (Blueprint $table) {
                $table->timestamp('published_at')->nullable()->after('is_public');
                $table->index('published_at');
            });

            // Backfill : tenant_reviews existants sont publiés
            DB::table('tenant_reviews')
                ->whereNull('published_at')
                ->update(['published_at' => DB::raw('updated_at')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reviews', 'published_at')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropIndex(['status', 'published_at']);
                $table->dropColumn('published_at');
            });
        }
        if (Schema::hasColumn('tenant_reviews', 'published_at')) {
            Schema::table('tenant_reviews', function (Blueprint $table) {
                $table->dropIndex(['published_at']);
                $table->dropColumn('published_at');
            });
        }
    }
};
