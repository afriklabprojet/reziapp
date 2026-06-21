<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Allow NULL so the INSERT succeeds before the observer sets the POINT value.
        // The ResidenceObserver::created() hook fills this column immediately after INSERT.
        DB::statement('ALTER TABLE residences MODIFY COLUMN location POINT NULL SRID 4326');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Re-backfill any rows that may have NULL before enforcing NOT NULL again.
        DB::statement(
            "UPDATE residences
             SET location = ST_GeomFromText(CONCAT('POINT(', longitude, ' ', latitude, ')'), 4326)
             WHERE location IS NULL AND latitude IS NOT NULL AND longitude IS NOT NULL",
        );

        DB::statement('ALTER TABLE residences MODIFY COLUMN location POINT NOT NULL SRID 4326');
    }
};
