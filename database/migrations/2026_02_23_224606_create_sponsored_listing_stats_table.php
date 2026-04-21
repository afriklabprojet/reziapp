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
        Schema::create('sponsored_listing_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsored_listing_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('contacts')->default(0);
            $table->decimal('amount_spent', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['sponsored_listing_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsored_listing_stats');
    }
};
