<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ical_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // ex: Airbnb, Booking.com
            $table->string('platform')->nullable(); // airbnb, booking, expedia, other
            $table->text('import_url')->nullable(); // URL iCal à importer
            $table->string('export_token')->unique(); // token unique pour l'URL d'export
            $table->string('sync_status')->default('pending'); // pending, syncing, synced, error
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->integer('imported_events_count')->default(0);
            $table->boolean('auto_sync')->default(true);
            $table->integer('sync_interval_minutes')->default(60);
            $table->timestamps();

            $table->index(['residence_id', 'platform']);
        });

        Schema::create('ical_blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ical_feed_id')->constrained()->onDelete('cascade');
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('summary')->nullable(); // titre de l'événement iCal
            $table->string('uid')->nullable(); // UID iCal unique
            $table->timestamps();

            $table->index(['residence_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ical_blocked_dates');
        Schema::dropIfExists('ical_feeds');
    }
};
