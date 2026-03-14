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
        Schema::create('co_hosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade'); // Propriétaire principal
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Co-hôte (si inscrit)
            $table->string('email'); // Email d'invitation
            $table->string('name');
            $table->string('phone')->nullable();

            // Permissions granulaires
            $table->boolean('can_edit_listing')->default(false);
            $table->boolean('can_manage_calendar')->default(true);
            $table->boolean('can_manage_pricing')->default(false);
            $table->boolean('can_respond_messages')->default(true);
            $table->boolean('can_accept_bookings')->default(false);
            $table->boolean('can_view_earnings')->default(false);

            // Commission co-hôte
            $table->decimal('commission_percent', 5, 2)->nullable();

            // Statut invitation
            $table->enum('status', ['pending', 'accepted', 'declined', 'revoked'])->default('pending');
            $table->string('invitation_token')->nullable()->unique();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['residence_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('invitation_token');
        });

        // Historique des actions co-hôtes
        Schema::create('co_host_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('co_host_id')->constrained()->onDelete('cascade');
            $table->foreignId('residence_id')->constrained()->onDelete('cascade');
            $table->string('action'); // 'price_updated', 'booking_accepted', 'message_sent', etc.
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['co_host_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('co_host_activities');
        Schema::dropIfExists('co_hosts');
    }
};
