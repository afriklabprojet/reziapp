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
        Schema::table('seo_data', function (Blueprint $table) {
            $table->string('route_name')->nullable()->after('locale');
            $table->string('url_pattern')->nullable()->after('route_name');
            $table->string('page_type', 50)->nullable()->after('url_pattern');
            $table->boolean('is_noindex')->default(false)->after('canonical_url');
            $table->boolean('is_nofollow')->default(false)->after('is_noindex');
            $table->decimal('priority', 3, 1)->default(0.5)->after('is_nofollow');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_data', function (Blueprint $table) {
            $table->dropColumn(['route_name', 'url_pattern', 'page_type', 'is_noindex', 'is_nofollow', 'priority']);
        });
    }
};
