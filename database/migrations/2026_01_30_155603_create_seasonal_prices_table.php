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
        Schema::create('seasonal_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Ex: "Haute saison", "Noël", "Vacances"
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('price_per_night', 10, 2);
            $table->decimal('price_per_week', 10, 2)->nullable();
            $table->decimal('price_per_month', 10, 2)->nullable();
            $table->integer('min_nights')->default(1);
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['residence_id', 'start_date', 'end_date']);
            $table->index(['start_date', 'end_date', 'is_active']);
        });

        // Table pour les prix par jour spécifique (override)
        Schema::create('daily_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->date('date')->index();
            $table->decimal('price', 10, 2);
            $table->boolean('is_available')->default(true);
            $table->string('reason')->nullable(); // Ex: "Jour férié", "Événement"
            $table->timestamps();

            $table->unique(['residence_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_prices');
        Schema::dropIfExists('seasonal_prices');
    }
};
