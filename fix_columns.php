<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ajouter les colonnes manquantes à residences
Schema::table('residences', function (Blueprint $table) {
    if (!Schema::hasColumn('residences', 'deposit_negotiable')) {
        $table->boolean('deposit_negotiable')->default(false)->after('rental_type');
    }
    if (!Schema::hasColumn('residences', 'deposit_terms')) {
        $table->text('deposit_terms')->nullable()->after('deposit_negotiable');
    }
    if (!Schema::hasColumn('residences', 'lease_type')) {
        $table->enum('lease_type', ['written', 'verbal', 'flexible'])->default('written')->after('deposit_terms');
    }
    if (!Schema::hasColumn('residences', 'target_tenants')) {
        $table->json('target_tenants')->nullable()->after('lease_type');
    }
    if (!Schema::hasColumn('residences', 'performance_score')) {
        $table->decimal('performance_score', 5, 2)->default(0)->after('views_count');
    }
    if (!Schema::hasColumn('residences', 'response_rate')) {
        $table->decimal('response_rate', 5, 2)->default(0)->after('performance_score');
    }
    if (!Schema::hasColumn('residences', 'avg_response_time_hours')) {
        $table->integer('avg_response_time_hours')->nullable()->after('response_rate');
    }
});

// Ajouter flag_emoji aux countries
if (!Schema::hasColumn('countries', 'flag_emoji')) {
    Schema::table('countries', function (Blueprint $table) {
        $table->string('flag_emoji', 10)->nullable()->after('currency_name');
    });
}

// Mettre à jour Côte d'Ivoire avec le flag emoji
DB::table('countries')->where('code', 'CI')->update(['flag_emoji' => '🇨🇮']);

echo "Corrections appliquées!\n";
