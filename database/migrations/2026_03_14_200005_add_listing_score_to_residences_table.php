<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->unsignedTinyInteger('listing_score')->nullable()->after('status')
                ->comment('Score qualité annonce (0-100)');
            $table->json('listing_score_breakdown')->nullable()->after('listing_score')
                ->comment('Détail du score par critère');
            $table->timestamp('listing_score_computed_at')->nullable()->after('listing_score_breakdown');
        });
    }

    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn(['listing_score', 'listing_score_breakdown', 'listing_score_computed_at']);
        });
    }
};
