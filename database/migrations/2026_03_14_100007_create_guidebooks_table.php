<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('guidebooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('welcome_message')->nullable();
            $table->string('wifi_name')->nullable();
            $table->string('wifi_password')->nullable();
            $table->text('house_rules_details')->nullable();
            $table->text('parking_info')->nullable();
            $table->text('transport_info')->nullable();
            $table->text('emergency_info')->nullable();
            $table->string('checkout_instructions')->nullable();
            $table->string('access_token')->unique(); // token public pour le voyageur
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index('residence_id');
        });

        Schema::create('guidebook_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guidebook_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('icon')->nullable(); // heroicon name
            $table->text('content');
            $table->json('photos')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['guidebook_id', 'sort_order']);
        });

        Schema::create('guidebook_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guidebook_id')->constrained()->onDelete('cascade');
            $table->string('category'); // restaurant, cafe, shopping, activity, pharmacy, supermarket, transport
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('photo')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['guidebook_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guidebook_recommendations');
        Schema::dropIfExists('guidebook_sections');
        Schema::dropIfExists('guidebooks');
    }
};
