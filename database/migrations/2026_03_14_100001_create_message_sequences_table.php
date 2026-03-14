<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('message_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('residence_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('trigger_event'); // booking_confirmed, check_in_approaching, post_checkout, pre_checkout
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('message_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_sequence_id')->constrained()->onDelete('cascade');
            $table->integer('step_order');
            $table->integer('delay_hours')->default(0); // heures relatives à l'événement déclencheur
            $table->string('delay_reference')->default('after_trigger'); // after_trigger, before_checkin, after_checkout, before_checkout
            $table->string('channel')->default('email'); // email, sms, whatsapp, in_app
            $table->string('subject')->nullable();
            $table->text('message');
            $table->json('variables')->nullable(); // {guest_name}, {residence_name}, {check_in_date}, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['message_sequence_id', 'step_order']);
        });

        Schema::create('message_sequence_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_sequence_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('step_id');
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // destinataire
            $table->string('channel');
            $table->string('status')->default('pending'); // pending, sent, failed, cancelled
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->foreign('step_id')->references('id')->on('message_sequence_steps')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_sequence_logs');
        Schema::dropIfExists('message_sequence_steps');
        Schema::dropIfExists('message_sequences');
    }
};
