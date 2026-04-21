<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Supprime le type 'location_maison' de l'enum type_location.
     * Les résidences existantes avec ce type sont converties en 'apartment'.
     */
    public function up(): void
    {
        // Convertir les résidences location_maison existantes en apartment
        DB::table('residences')
            ->where('type_location', 'location_maison')
            ->update([
                'type_location' => 'apartment',
                'price_period' => 'month',
            ]);

        // SQLite ne supporte pas MODIFY COLUMN / ENUM — on skip silencieusement
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Retirer 'location_maison' de l'enum
        DB::statement("ALTER TABLE residences MODIFY COLUMN type_location ENUM('apartment', 'residence_meublee', 'hotel') DEFAULT 'residence_meublee'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE residences MODIFY COLUMN type_location ENUM('apartment', 'residence_meublee', 'hotel', 'location_maison') DEFAULT 'residence_meublee'");
    }
};
