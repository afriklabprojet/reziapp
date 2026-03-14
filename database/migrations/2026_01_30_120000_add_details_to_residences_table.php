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
        Schema::table('residences', function (Blueprint $table) {
            $table->integer('bedrooms')->default(1)->after('price_per_month');
            $table->integer('bathrooms')->default(1)->after('bedrooms');
            $table->enum('type', ['studio', 'apartment', 'house', 'villa', 'duplex', 'other'])->default('apartment')->after('bathrooms');
            $table->integer('surface_area')->nullable()->after('type'); // m²
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn(['bedrooms', 'bathrooms', 'type', 'surface_area']);
        });
    }
};
