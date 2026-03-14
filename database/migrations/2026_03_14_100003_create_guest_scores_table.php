<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('guest_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('total_score')->default(50); // 0-100
            $table->integer('identity_score')->default(0); // 0-25 (KYC, phone, email verified)
            $table->integer('booking_score')->default(0); // 0-25 (completed bookings, cancellation rate)
            $table->integer('review_score')->default(0); // 0-25 (average rating from owners)
            $table->integer('seniority_score')->default(0); // 0-25 (account age, activity)
            $table->string('risk_level')->default('medium'); // low, medium, high
            $table->integer('total_bookings')->default(0);
            $table->integer('completed_bookings')->default(0);
            $table->integer('cancelled_bookings')->default(0);
            $table->decimal('cancellation_rate', 5, 2)->default(0);
            $table->decimal('average_owner_rating', 3, 2)->default(0);
            $table->integer('damage_reports_count')->default(0);
            $table->json('flags')->nullable(); // flagged issues
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('risk_level');
            $table->index('total_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_scores');
    }
};
