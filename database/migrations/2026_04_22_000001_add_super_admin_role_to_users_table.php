<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        // MySQL : modifier l'enum directement via ALTER TABLE
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'owner', 'admin', 'super_admin') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        // Rétrograder les super_admin → admin avant de retirer la valeur
        DB::statement("UPDATE users SET role = 'admin' WHERE role = 'super_admin'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'owner', 'admin') NOT NULL DEFAULT 'user'");
    }
};
