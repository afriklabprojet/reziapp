<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('utility_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('utility_type'); // electricity, water, gas, internet
            $table->decimal('reading_value', 12, 2);
            $table->string('unit')->default('kWh'); // kWh, m3, L, GB
            $table->date('reading_date');
            $table->string('reading_type')->default('manual'); // manual, automatic, meter_photo
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['residence_id', 'utility_type', 'reading_date']);
        });

        Schema::create('utility_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('utility_type');
            $table->string('alert_type'); // high_consumption, abnormal_spike, threshold_exceeded
            $table->decimal('threshold_value', 12, 2)->nullable();
            $table->decimal('current_value', 12, 2)->nullable();
            $table->string('status')->default('active'); // active, acknowledged, resolved
            $table->text('message')->nullable();
            $table->timestamp('triggered_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['residence_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_alerts');
        Schema::dropIfExists('utility_readings');
    }
};
