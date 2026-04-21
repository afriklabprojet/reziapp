<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('availability_calendar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['available', 'blocked', 'booked'])->default('available');
            $table->decimal('custom_price', 12, 2)->nullable(); // Prix personnalisé pour cette date
            $table->decimal('min_nights', 5, 2)->nullable(); // Minimum nuits pour cette période
            $table->string('note')->nullable(); // Note interne propriétaire
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            $table->unique(['residence_id', 'date']);
            $table->index(['residence_id', 'status']);
            $table->index(['date', 'status']);
        });

        // Table pour les règles de prix saisonniers
        Schema::create('seasonal_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Ex: "Haute saison", "Noël"
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('price_per_day', 12, 2)->nullable();
            $table->decimal('price_per_week', 12, 2)->nullable();
            $table->decimal('price_per_month', 12, 2)->nullable();
            $table->decimal('price_multiplier', 5, 2)->default(1.00); // Ex: 1.5 = +50%
            $table->unsignedTinyInteger('min_nights')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['residence_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasonal_pricing');
        Schema::dropIfExists('availability_calendar');
    }
};
