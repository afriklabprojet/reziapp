<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('business_events', function (Blueprint $table) {
            $table->id();
            $table->string('event', 100)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            // Composite index for dashboard queries
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_events');
    }
};
