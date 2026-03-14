<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('point_of_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->decimal('distance_meters', 10, 2);
            $table->integer('walking_time_minutes')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();

            $table->index(['residence_id', 'type']);
            $table->index(['residence_id', 'distance_meters']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_of_interests');
    }
};
