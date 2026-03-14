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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Messages - par canal
            $table->boolean('messages_email')->default(true);
            $table->boolean('messages_push')->default(true);
            $table->boolean('messages_sms')->default(false);

            // Visites - par canal
            $table->boolean('visits_email')->default(true);
            $table->boolean('visits_push')->default(true);
            $table->boolean('visits_sms')->default(false);

            // Paiements - par canal
            $table->boolean('payments_email')->default(true);
            $table->boolean('payments_push')->default(true);
            $table->boolean('payments_sms')->default(true);

            // Marketing - par canal
            $table->boolean('marketing_email')->default(false);
            $table->boolean('marketing_push')->default(false);
            $table->boolean('marketing_sms')->default(false);

            // Sécurité - par canal
            $table->boolean('security_email')->default(true);
            $table->boolean('security_push')->default(true);
            $table->boolean('security_sms')->default(true);

            // Horaires silencieux
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->string('timezone', 50)->default('Africa/Abidjan');

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
