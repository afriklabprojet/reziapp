<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->boolean('pets_allowed')->default(false)->after('house_rules');
            $table->boolean('smoking_allowed')->default(false)->after('pets_allowed');
            $table->boolean('parties_allowed')->default(false)->after('smoking_allowed');
            $table->integer('floor')->nullable()->after('parties_allowed');
            $table->boolean('has_elevator')->default(false)->after('floor');
        });
    }

    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn(['pets_allowed', 'smoking_allowed', 'parties_allowed', 'floor', 'has_elevator']);
        });
    }
};
