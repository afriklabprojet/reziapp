<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('support_tickets', 'dispute_id')) {
                $table->foreignId('dispute_id')
                    ->nullable()
                    ->after('residence_id')
                    ->constrained()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('support_tickets', 'dispute_id')) {
                $table->dropForeign(['dispute_id']);
                $table->dropColumn('dispute_id');
            }
        });
    }
};
