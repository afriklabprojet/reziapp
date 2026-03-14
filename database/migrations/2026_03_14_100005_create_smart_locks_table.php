<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('smart_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // ttlock, nuki, august, igloohome, other
            $table->string('device_id')->nullable();
            $table->string('device_name');
            $table->string('status')->default('active'); // active, inactive, offline, error
            $table->json('credentials')->nullable(); // encrypted API credentials
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['residence_id', 'status']);
        });

        Schema::create('smart_lock_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smart_lock_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('code_type')->default('temporary'); // temporary, permanent, one_time
            $table->string('status')->default('active'); // active, expired, revoked
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->string('guest_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index(['smart_lock_id', 'status']);
            $table->index(['booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_lock_codes');
        Schema::dropIfExists('smart_locks');
    }
};
