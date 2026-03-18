<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_contracts', function (Blueprint $table) {
            $table->decimal('charges_amount', 12, 2)->default(0)->after('deposit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('lease_contracts', function (Blueprint $table) {
            $table->dropColumn('charges_amount');
        });
    }
};
