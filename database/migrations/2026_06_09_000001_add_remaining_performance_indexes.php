<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the remaining indexes identified during the production audit.
 *
 * Index already present (verified against production 2026-06-09):
 *   conversations : user_id+last_message_at, owner_id+last_message_at ✓
 *   bookings      : user_id+status, residence_id+status, user_id+payment_status ✓
 *   reviews       : residence_id+status+rating, user_id ✓
 *   contacts      : owner_id+status, residence_id+created_at ✓
 *   subscriptions : user_id+status ✓
 *   blocked_dates : residence_id+start_date+end_date ✓
 *   favorites     : user_id+residence_id (unique) ✓
 *   notification_logs: user_id+channel+status ✓
 *
 * Still missing:
 *   messages(conversation_id, read_at) — used by unreadMessagesCount()
 *   reviews(user_id, status)           — used by user review history pages
 */
return new class () extends Migration {
    public function up(): void
    {
        // messages(conversation_id, read_at)
        // Used by: User::unreadMessagesCount() — WHERE conversation_id IN (...)
        //          AND sender_id != ? AND read_at IS NULL
        // The existing (conversation_id, created_at) index does not cover read_at
        // filtering and forces a full index scan + row filter on every page load.
        $msgIndexes = collect(Schema::getIndexes('messages'))->pluck('name')->toArray();
        if (! in_array('idx_messages_conv_read_at', $msgIndexes)) {
            Schema::table('messages', function (Blueprint $table) {
                $table->index(['conversation_id', 'read_at'], 'idx_messages_conv_read_at');
            });
        }

        // reviews(user_id, status)
        // Used by: review history pages, owner "reviews given" dashboard
        // The existing reviews_user_id_foreign is a single-column FK index;
        // adding status avoids a table scan when filtering by status.
        $reviewIndexes = collect(Schema::getIndexes('reviews'))->pluck('name')->toArray();
        if (! in_array('idx_reviews_user_status', $reviewIndexes)) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_reviews_user_status');
            });
        }
    }

    public function down(): void
    {
        $msgIndexes = collect(Schema::getIndexes('messages'))->pluck('name')->toArray();
        if (in_array('idx_messages_conv_read_at', $msgIndexes)) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropIndex('idx_messages_conv_read_at');
            });
        }

        $reviewIndexes = collect(Schema::getIndexes('reviews'))->pluck('name')->toArray();
        if (in_array('idx_reviews_user_status', $reviewIndexes)) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropIndex('idx_reviews_user_status');
            });
        }
    }
};
