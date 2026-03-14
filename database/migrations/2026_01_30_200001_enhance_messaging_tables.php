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
        // Ajouter des colonnes à la table messages existante
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'type')) {
                $table->enum('type', ['text', 'image', 'file', 'document', 'audio', 'location', 'system', 'auto_reply'])->default('text')->after('content');
            }
            if (!Schema::hasColumn('messages', 'attachments')) {
                $table->json('attachments')->nullable()->after('type');
            }
            if (!Schema::hasColumn('messages', 'metadata')) {
                $table->json('metadata')->nullable()->after('attachments');
            }
            if (!Schema::hasColumn('messages', 'is_auto_reply')) {
                $table->boolean('is_auto_reply')->default(false)->after('metadata');
            }
            if (!Schema::hasColumn('messages', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('is_auto_reply');
            }
            if (!Schema::hasColumn('messages', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('read_at');
            }
            if (!Schema::hasColumn('messages', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Ajouter des colonnes à la table conversations existante
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'status')) {
                $table->enum('status', ['active', 'archived', 'blocked'])->default('active')->after('last_message_at');
            }
            if (!Schema::hasColumn('conversations', 'unread_user_count')) {
                $table->integer('unread_user_count')->default(0)->after('status');
            }
            if (!Schema::hasColumn('conversations', 'unread_owner_count')) {
                $table->integer('unread_owner_count')->default(0)->after('unread_user_count');
            }
            if (!Schema::hasColumn('conversations', 'user_typing')) {
                $table->boolean('user_typing')->default(false)->after('unread_owner_count');
            }
            if (!Schema::hasColumn('conversations', 'owner_typing')) {
                $table->boolean('owner_typing')->default(false)->after('user_typing');
            }
            if (!Schema::hasColumn('conversations', 'user_last_seen_at')) {
                $table->timestamp('user_last_seen_at')->nullable()->after('owner_typing');
            }
            if (!Schema::hasColumn('conversations', 'owner_last_seen_at')) {
                $table->timestamp('owner_last_seen_at')->nullable()->after('user_last_seen_at');
            }
            if (!Schema::hasColumn('conversations', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $columns = ['type', 'attachments', 'metadata', 'is_auto_reply', 'template_id', 'delivered_at', 'deleted_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('conversations', function (Blueprint $table) {
            $columns = ['status', 'unread_user_count', 'unread_owner_count', 'user_typing', 'owner_typing', 'user_last_seen_at', 'owner_last_seen_at', 'deleted_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('conversations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
