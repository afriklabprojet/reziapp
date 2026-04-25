<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lease_contracts', function (Blueprint $table) {
            $table->timestamp('termination_requested_at')->nullable()->after('terminated_at');
            $table->string('termination_request_reason', 1000)->nullable()->after('termination_requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lease_contracts', function (Blueprint $table) {
            $table->dropColumn(['termination_requested_at', 'termination_request_reason']);
        });
    }
};
