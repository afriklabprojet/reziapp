<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Vérification téléphone par SMS OTP
     */
    public function up(): void
    {
        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('phone', 20);
            $table->string('country_code', 5)->default('+225'); // Côte d'Ivoire
            $table->string('otp_code', 6)->nullable();
            $table->timestamp('otp_expires_at')->nullable();

            $table->enum('status', ['pending', 'sent', 'verified', 'failed'])->default('pending');

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->string('verification_method')->default('sms'); // sms, whatsapp, call
            $table->string('provider')->nullable(); // Orange, MTN, etc.

            $table->timestamps();

            $table->index(['user_id', 'phone']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_verifications');
    }
};
