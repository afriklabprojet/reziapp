<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Expand the residences status enum to match all Filament admin statuses,
     * then migrate existing 'approved' records to 'active'.
     */
    public function up(): void
    {
        // SQLite ne supporte pas MODIFY COLUMN / ENUM — on skip silencieusement
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Step 1: Expand the enum to include all statuses used in Filament
        DB::statement("ALTER TABLE residences MODIFY COLUMN status ENUM('pending', 'approved', 'active', 'needs_changes', 'draft', 'inactive', 'rejected') NOT NULL DEFAULT 'pending'");

        // Step 2: Migrate existing 'approved' to 'active'
        DB::table('residences')
            ->where('status', 'approved')
            ->update(['status' => 'active']);

        // Step 3: Remove 'approved' from enum (no longer needed)
        DB::statement("ALTER TABLE residences MODIFY COLUMN status ENUM('pending', 'active', 'needs_changes', 'draft', 'inactive', 'rejected') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse: restore original enum with 'approved'.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Re-add 'approved' to enum
        DB::statement("ALTER TABLE residences MODIFY COLUMN status ENUM('pending', 'approved', 'active', 'needs_changes', 'draft', 'inactive', 'rejected') NOT NULL DEFAULT 'pending'");

        // Revert 'active' back to 'approved'
        DB::table('residences')
            ->where('status', 'active')
            ->update(['status' => 'approved']);

        // Remove the new statuses
        DB::statement("ALTER TABLE residences MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
