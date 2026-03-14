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
        Schema::create('auto_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('residence_id')->nullable()->constrained()->onDelete('cascade'); // null = global
            $table->string('name'); // Ex: "Bienvenue", "Indisponible", "Check-in"
            $table->string('trigger_type'); // 'first_contact', 'keywords', 'schedule', 'manual'
            $table->json('trigger_conditions')->nullable(); // mots-clés, horaires, etc.
            $table->text('message');
            $table->boolean('is_active')->default(true);
            $table->integer('delay_minutes')->default(0); // Délai avant envoi
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['residence_id', 'trigger_type']);
        });

        // Table pour les templates prédéfinis
        Schema::create('reply_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // 'welcome', 'booking', 'checkin', 'checkout', 'faq'
            $table->text('message');
            $table->json('variables')->nullable(); // Variables disponibles: {guest_name}, {residence_name}, etc.
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reply_templates');
        Schema::dropIfExists('auto_replies');
    }
};
