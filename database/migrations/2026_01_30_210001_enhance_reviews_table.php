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
        // Améliorer la table reviews
        Schema::table('reviews', function (Blueprint $table) {
            // Notes multi-critères supplémentaires
            if (!Schema::hasColumn('reviews', 'accuracy_rating')) {
                $table->tinyInteger('accuracy_rating')->nullable()->after('communication_rating'); // Conformité photos
            }
            if (!Schema::hasColumn('reviews', 'checkin_rating')) {
                $table->tinyInteger('checkin_rating')->nullable()->after('accuracy_rating'); // Arrivée/accueil
            }

            // Vérification du séjour
            if (!Schema::hasColumn('reviews', 'booking_id')) {
                $table->foreignId('booking_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('reviews', 'stay_date_start')) {
                $table->date('stay_date_start')->nullable()->after('is_verified');
            }
            if (!Schema::hasColumn('reviews', 'stay_date_end')) {
                $table->date('stay_date_end')->nullable()->after('stay_date_start');
            }

            // Photos de l'avis
            if (!Schema::hasColumn('reviews', 'photos')) {
                $table->json('photos')->nullable()->after('comment');
            }

            // Recommandation
            if (!Schema::hasColumn('reviews', 'would_recommend')) {
                $table->boolean('would_recommend')->nullable()->after('photos');
            }

            // Points forts/faibles
            if (!Schema::hasColumn('reviews', 'pros')) {
                $table->json('pros')->nullable()->after('would_recommend');
            }
            if (!Schema::hasColumn('reviews', 'cons')) {
                $table->json('cons')->nullable()->after('pros');
            }

            // Helpful votes
            if (!Schema::hasColumn('reviews', 'helpful_count')) {
                $table->unsignedInteger('helpful_count')->default(0)->after('cons');
            }

            // Modération
            if (!Schema::hasColumn('reviews', 'moderation_notes')) {
                $table->text('moderation_notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('reviews', 'moderated_by')) {
                $table->foreignId('moderated_by')->nullable()->after('moderation_notes');
            }
            if (!Schema::hasColumn('reviews', 'moderated_at')) {
                $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $columns = [
                'accuracy_rating', 'checkin_rating', 'booking_id',
                'stay_date_start', 'stay_date_end', 'photos',
                'would_recommend', 'pros', 'cons', 'helpful_count',
                'moderation_notes', 'moderated_by', 'moderated_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('reviews', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
