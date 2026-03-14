<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajouter des index composites sur conversations pour optimiser
 * la requête WHERE user_id = ? OR owner_id = ? ORDER BY last_message_at DESC
 * utilisée sur chaque chargement de la page de messagerie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['user_id', 'last_message_at'], 'idx_conversations_user_last_msg');
            $table->index(['owner_id', 'last_message_at'], 'idx_conversations_owner_last_msg');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('idx_conversations_user_last_msg');
            $table->dropIndex('idx_conversations_owner_last_msg');
        });
    }
};
