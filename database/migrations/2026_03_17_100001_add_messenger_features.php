<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Table des réactions emoji sur les messages
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('emoji', 10); // 👍❤️😂😮😢😡
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'emoji']);
            $table->index(['message_id']);
        });

        // Ajout de theme_color et last_seen_at sur conversations
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('theme_color', 20)->default('orange')->after('status');
        });

        // Ajout du type voice et champ link_preview sur messages
        Schema::table('messages', function (Blueprint $table) {
            $table->json('link_preview')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('theme_color');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('link_preview');
        });
    }
};
