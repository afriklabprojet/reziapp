<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Add wallet and referral credit tracking columns to payments table.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('wallet_credit_used', 12, 2)->default(0)->after('fee');
            $table->decimal('referral_credit_used', 12, 2)->default(0)->after('wallet_credit_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['wallet_credit_used', 'referral_credit_used']);
        });
    }
};
