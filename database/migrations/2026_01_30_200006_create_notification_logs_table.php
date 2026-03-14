<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type', 100);
            $table->string('channel', 50); // email, sms, push, in_app
            $table->string('title', 255)->nullable();
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->string('status', 50)->default('pending'); // pending, sent, delivered, failed, read, clicked
            $table->string('error_message', 500)->nullable();
            $table->string('external_id', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'channel', 'status']);
            $table->index(['created_at']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
