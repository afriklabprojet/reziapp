<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'identity_verified_at')) {
                $table->timestamp('identity_verified_at')->nullable()->after('identity_verified');
            }
            if (!Schema::hasColumn('users', 'identity_verification_status')) {
                $table->string('identity_verification_status')->nullable()->after('identity_verified_at');
            }
            if (!Schema::hasColumn('users', 'identity_verification_data')) {
                $table->json('identity_verification_data')->nullable()->after('identity_verification_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['identity_verified_at', 'identity_verification_status', 'identity_verification_data']);
        });
    }
};
