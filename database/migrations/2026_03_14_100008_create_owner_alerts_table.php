<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('owner_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('residence_id')->nullable()->constrained()->nullOnDelete();
            $table->string('alert_type'); // response_time_sla, low_occupancy, price_suggestion, review_pending, document_expiry, high_consumption, booking_gap
            $table->string('severity')->default('info'); // info, warning, critical
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->string('status')->default('active'); // active, acknowledged, resolved, dismissed
            $table->string('action_url')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['alert_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_alerts');
    }
};
