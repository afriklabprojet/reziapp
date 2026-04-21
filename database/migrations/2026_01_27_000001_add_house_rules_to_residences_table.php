<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('residences')) {
            return;
        }

        Schema::table('residences', function (Blueprint $table) {
            if (!Schema::hasColumn('residences', 'pets_allowed')) {
                $table->boolean('pets_allowed')->default(false);
            }
            if (!Schema::hasColumn('residences', 'smoking_allowed')) {
                $table->boolean('smoking_allowed')->default(false);
            }
            if (!Schema::hasColumn('residences', 'parties_allowed')) {
                $table->boolean('parties_allowed')->default(false);
            }
            if (!Schema::hasColumn('residences', 'floor')) {
                $table->integer('floor')->nullable();
            }
            if (!Schema::hasColumn('residences', 'has_elevator')) {
                $table->boolean('has_elevator')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn(['pets_allowed', 'smoking_allowed', 'parties_allowed', 'floor', 'has_elevator']);
        });
    }
};
