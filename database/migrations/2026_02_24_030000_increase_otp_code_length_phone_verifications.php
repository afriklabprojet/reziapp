<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('phone_verifications', function (Blueprint $table) {
            // Bcrypt hashes are 60 chars; allow 255 for future-proofing
            $table->string('otp_code', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('phone_verifications', function (Blueprint $table) {
            $table->string('otp_code', 6)->nullable()->change();
        });
    }
};
