<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Création des tables pays, villes et communes
     * pour supporter CI + Burkina Faso (et au-delà)
     */
    public function up(): void
    {
        // Pays
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');              // Côte d'Ivoire
            $table->string('code', 2)->unique(); // CI, BF
            $table->string('phone_code', 5);     // +225, +226
            $table->string('currency', 5)->default('XOF');
            $table->decimal('latitude', 10, 7);  // Centre du pays
            $table->decimal('longitude', 11, 7);
            // Bounding box du pays
            $table->decimal('min_lat', 10, 7);
            $table->decimal('max_lat', 10, 7);
            $table->decimal('min_lng', 11, 7);
            $table->decimal('max_lng', 11, 7);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Villes
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 11, 7);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['country_id', 'slug']);
        });

        // Communes (quartiers/arrondissements dans une ville)
        Schema::create('communes_list', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 11, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['city_id', 'slug']);
        });

        // Ajouter city_id et country_code sur residences
        Schema::table('residences', function (Blueprint $table) {
            $table->string('country_code', 2)->default('CI')->after('commune');
            $table->string('city')->nullable()->after('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'city']);
        });

        Schema::dropIfExists('communes_list');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
