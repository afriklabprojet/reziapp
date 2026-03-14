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
        Schema::table('message_templates', function (Blueprint $table) {
            // Rendre user_id nullable pour les templates système
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Ajouter les colonnes manquantes
            if (!Schema::hasColumn('message_templates', 'shortcut')) {
                $table->string('shortcut', 20)->nullable()->after('content');
            }

            if (!Schema::hasColumn('message_templates', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('is_active');
            }
        });

        // Ajouter index sur shortcut si n'existe pas
        Schema::table('message_templates', function (Blueprint $table) {
            $table->index('shortcut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropIndex(['shortcut']);
            $table->dropColumn(['shortcut', 'is_system']);
        });
    }
};
