<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->enum('type_location', ['apartment', 'residence_meublee', 'hotel'])
                ->default('residence_meublee')
                ->after('rental_type')
                ->index();

            $table->enum('price_period', ['day', 'night', 'month'])
                ->default('day')
                ->after('type_location');
        });
    }

    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropIndex(['type_location']);
            $table->dropColumn(['type_location', 'price_period']);
        });
    }
};
