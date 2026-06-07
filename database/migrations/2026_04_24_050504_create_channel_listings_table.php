<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('channel_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->enum('channel', ['airbnb', 'booking', 'expedia', 'vrbo']);
            $table->string('external_id')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->enum('sync_status', ['pending', 'syncing', 'success', 'error'])->default('pending');
            $table->text('sync_message')->nullable();
            $table->json('sync_metadata')->nullable();
            $table->timestamps();
            $table->unique(['residence_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_listings');
    }
};
