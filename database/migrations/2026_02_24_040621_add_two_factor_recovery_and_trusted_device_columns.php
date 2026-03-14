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
        Schema::table('users', function (Blueprint $table) {
            // Codes de récupération chiffrés (JSON array de 8 codes hashés)
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            // Token de l'appareil de confiance (cookie signé)
            $table->string('trusted_device_token', 100)->nullable()->after('two_factor_recovery_codes');
            // Expiration de l'appareil de confiance
            $table->timestamp('trusted_device_expires_at')->nullable()->after('trusted_device_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_recovery_codes',
                'trusted_device_token',
                'trusted_device_expires_at',
            ]);
        });
    }
};
