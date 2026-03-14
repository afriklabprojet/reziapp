<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Campagnes marketing (email/SMS/notifications)
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // créateur (admin)

            $table->string('name');
            $table->text('description')->nullable();

            // Type de campagne
            $table->enum('type', ['email', 'sms', 'push', 'in_app'])->default('email');

            // Contenu
            $table->string('subject')->nullable(); // Pour email
            $table->longText('content'); // Corps du message
            $table->string('template')->nullable(); // Template à utiliser

            // Ciblage
            $table->enum('audience', [
                'all_users',
                'owners',
                'clients',
                'inactive_users',
                'new_users',
                'high_value',
                'custom',
            ])->default('all_users');
            $table->json('audience_filters')->nullable(); // Filtres personnalisés
            $table->json('excluded_user_ids')->nullable();

            // Planification
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('sent_at')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'cancelled'])->default('draft');

            // Statistiques
            $table->integer('recipients_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('bounced_count')->default(0);
            $table->integer('unsubscribed_count')->default(0);

            // Options
            $table->boolean('track_opens')->default(true);
            $table->boolean('track_clicks')->default(true);

            $table->timestamps();

            $table->index('status');
            $table->index('scheduled_at');
        });

        // Suivi des envois individuels
        Schema::create('campaign_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->enum('status', ['pending', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed'])->default('pending');
            $table->datetime('sent_at')->nullable();
            $table->datetime('delivered_at')->nullable();
            $table->datetime('opened_at')->nullable();
            $table->datetime('clicked_at')->nullable();
            $table->string('error_message')->nullable();

            $table->timestamps();

            $table->unique(['campaign_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sends');
        Schema::dropIfExists('campaigns');
    }
};
