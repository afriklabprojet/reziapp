<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Score sentiment Google NLP (-1.0 à 1.0)
            $table->float('sentiment_score')->nullable()->after('comment');
            // Label lisible : très_positif, positif, neutre, négatif, très_négatif
            $table->string('sentiment_label')->nullable()->after('sentiment_score');
            // Flag modération automatique (sentiment très négatif)
            $table->boolean('needs_moderation')->default(false)->after('sentiment_label');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['sentiment_score', 'sentiment_label', 'needs_moderation']);
        });
    }
};
