<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('damage_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('security_deposit_id')->nullable();
            $table->string('title');
            $table->text('description');
            $table->string('category'); // furniture, appliance, plumbing, electrical, structural, cosmetic, other
            $table->string('severity'); // minor, moderate, major, critical
            $table->decimal('estimated_cost', 10, 0)->default(0);
            $table->decimal('actual_cost', 10, 0)->nullable();
            $table->decimal('deducted_amount', 10, 0)->default(0);
            $table->json('photos')->nullable();
            $table->string('status')->default('reported'); // reported, assessed, repair_scheduled, repaired, deducted, closed
            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('repaired_at')->nullable();
            $table->text('repair_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['residence_id', 'status']);
            $table->index(['booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_reports');
    }
};
