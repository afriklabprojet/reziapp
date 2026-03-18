<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds idempotency keys for payments/bookings and webhook event tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotency key on payments to prevent double charges
        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'idempotency_key')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('idempotency_key', 64)->nullable()->unique()->after('uuid');
                $table->unsignedTinyInteger('retry_count')->default(0)->after('expires_at');
                $table->timestamp('last_retry_at')->nullable()->after('retry_count');
            });
        }

        // Idempotency key on bookings to prevent double bookings from rapid clicks
        if (Schema::hasTable('bookings') && ! Schema::hasColumn('bookings', 'idempotency_key')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('idempotency_key', 64)->nullable()->unique()->after('uuid');
            });
        }

        // Webhook event tracking to prevent double-processing
        if (! Schema::hasTable('webhook_events')) {
            Schema::create('webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 20)->index();           // jeko, whatsapp, etc.
                $table->string('event_id', 128)->index();           // provider's unique event/txn ID
                $table->string('event_type', 50)->nullable();       // transaction.completed, etc.
                $table->string('status', 20)->default('processed'); // processed, failed
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->unique(['provider', 'event_id'], 'webhook_events_provider_event_unique');
            });
        }

        // Health check / monitoring table
        if (! Schema::hasTable('system_health_checks')) {
            Schema::create('system_health_checks', function (Blueprint $table) {
                $table->id();
                $table->string('component', 50);     // database, cache, queue, jeko, storage
                $table->string('status', 20);         // ok, degraded, down
                $table->unsignedInteger('response_ms')->default(0);
                $table->text('details')->nullable();
                $table->timestamp('checked_at');

                $table->index(['component', 'checked_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_checks');
        Schema::dropIfExists('webhook_events');

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'idempotency_key')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('idempotency_key');
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'idempotency_key')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn(['idempotency_key', 'retry_count', 'last_retry_at']);
            });
        }
    }
};
