<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sponsored_listings', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_days')->default(7)->after('ends_at');
            // Make starts_at/ends_at nullable (set after payment)
            $table->dateTime('starts_at')->nullable()->change();
            $table->dateTime('ends_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sponsored_listings', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });
    }
};
