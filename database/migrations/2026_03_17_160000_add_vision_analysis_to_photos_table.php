<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            // Auto-tagging
            $table->json('tags')->nullable()->after('is_optimized');
            $table->string('room_type', 50)->nullable()->after('tags');

            // Modération SafeSearch
            $table->enum('moderation_status', ['pending', 'approved', 'review', 'rejected', 'skipped'])
                ->default('pending')
                ->after('room_type');
            $table->string('moderation_reason')->nullable()->after('moderation_status');

            // Qualité
            $table->unsignedTinyInteger('quality_score')->nullable()->after('moderation_reason');
            $table->json('quality_issues')->nullable()->after('quality_score');

            // Vision API raw
            $table->json('safe_search_data')->nullable()->after('quality_issues');
            $table->json('labels_data')->nullable()->after('safe_search_data');

            // Détection doublons
            $table->string('image_hash', 16)->nullable()->after('labels_data');
            $table->boolean('is_property_photo')->default(true)->after('image_hash');

            // Index pour recherche doublons
            $table->index('image_hash');
            $table->index('moderation_status');
            $table->index('room_type');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropIndex(['image_hash']);
            $table->dropIndex(['moderation_status']);
            $table->dropIndex(['room_type']);

            $table->dropColumn([
                'tags', 'room_type',
                'moderation_status', 'moderation_reason',
                'quality_score', 'quality_issues',
                'safe_search_data', 'labels_data',
                'image_hash', 'is_property_photo',
            ]);
        });
    }
};
