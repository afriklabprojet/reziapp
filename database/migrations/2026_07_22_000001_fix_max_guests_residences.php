<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corriger max_guests pour toutes les résidences.
     *
     * La valeur par défaut de la migration précédente était 2, ce qui est
     * trop bas pour des maisons familiales. On recalcule la capacité réelle
     * d'après le nombre de chambres.
     */
    public function up(): void
    {
        // Changer le défaut de la colonne à 8
        Schema::table('residences', function (Blueprint $table) {
            $table->integer('max_guests')->default(8)->change();
        });

        // Mettre à jour les résidences encore à la valeur magique 2 (défaut migration)
        // avec une capacité calculée depuis le nombre de chambres :
        //   1 chambre → 2 · studios / 1-ch
        //   2 chambres → 4
        //   3 chambres → 6
        //   4+ chambres → 8
        DB::statement("
            UPDATE residences
            SET max_guests = CASE
                WHEN bedrooms >= 4 THEN 8
                WHEN bedrooms = 3  THEN 6
                WHEN bedrooms = 2  THEN 4
                ELSE 2
            END
            WHERE max_guests <= 2
        ");
    }

    public function down(): void
    {
        // Revenir au défaut 2 (ne retouche pas les valeurs déjà personnalisées)
        Schema::table('residences', function (Blueprint $table) {
            $table->integer('max_guests')->default(2)->change();
        });
    }
};
