<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite ne supporte pas MODIFY COLUMN / ENUM — on skip silencieusement
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Ajouter 'location_maison' à l'enum type_location
        DB::statement("ALTER TABLE residences MODIFY COLUMN type_location ENUM('apartment', 'residence_meublee', 'hotel', 'location_maison') DEFAULT 'residence_meublee'");
    }

    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Revenir à l'ancien enum
        DB::statement("ALTER TABLE residences MODIFY COLUMN type_location ENUM('apartment', 'residence_meublee', 'hotel') DEFAULT 'residence_meublee'");
    }
};
