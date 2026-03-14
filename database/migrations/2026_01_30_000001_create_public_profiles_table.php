<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->json('languages')->nullable();
            $table->string('location')->nullable();
            $table->string('work')->nullable();
            $table->decimal('response_time_hours', 8, 2)->nullable();
            $table->decimal('response_rate', 5, 2)->default(0);
            $table->boolean('is_superhost')->default(false);
            $table->date('superhost_since')->nullable();
            $table->unsignedInteger('total_reviews_given')->default(0);
            $table->unsignedInteger('total_reviews_received')->default(0);
            $table->date('member_since')->nullable();
            $table->boolean('show_email')->default(false);
            $table->boolean('show_phone')->default(false);
            $table->unsignedInteger('profile_views')->default(0);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_profiles');
    }
};
