<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('owner_badges', function (Blueprint $table) {
            // La resource Filament référence `status` (active/pending/suspended/revoked)
            // mais la table n'avait que `is_visible` — on ajoute le champ manquant
            $table->string('status', 20)->default('active')->after('is_visible');
            $table->index('status');
        });

        // Synchroniser les données existantes : is_visible=false → 'revoked', sinon 'active'
        DB::table('owner_badges')->update(['status' => DB::raw(
            "CASE WHEN is_visible = 0 THEN 'revoked' ELSE 'active' END",
        )]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_badges', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
