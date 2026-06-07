<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a POINT column with SRID 4326 and a SPATIAL INDEX to the residences
 * table, then backfills from the existing latitude/longitude columns.
 *
 * MySQL SPATIAL INDEX usage pattern:
 *   MBRContains(<bbox>, location)  → uses the index (fast O(log n))
 *   ST_Distance_Sphere(location, <point>) <= radius  → precise refinement
 */
return new class () extends Migration {
    public function up(): void
    {
        // SQLite (used in tests) does not support MySQL SPATIAL types or SRID.
        // Skip silently so the test suite can run without a MySQL connection.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('residences', function (Blueprint $table) {
            // NOT NULL requires a geometry default; we backfill immediately
            // after adding the column. Using DB::statement for full MySQL SPATIAL syntax.
        });

        // Blueprint does not expose SRID via column definition in all Laravel versions,
        // so we use raw DDL for precise control.
        DB::statement('ALTER TABLE residences ADD COLUMN location POINT NOT NULL SRID 4326 AFTER longitude');

        // Backfill: ST_GeomFromText expects (longitude latitude) for SRID 4326
        DB::statement(
            "UPDATE residences
             SET location = ST_GeomFromText(CONCAT('POINT(', longitude, ' ', latitude, ')'), 4326)
             WHERE latitude IS NOT NULL AND longitude IS NOT NULL",
        );

        DB::statement('CREATE SPATIAL INDEX idx_residences_location ON residences (location)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE residences DROP INDEX idx_residences_location');

        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
