<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Add Jeko payment gateway columns to sponsored_listings table.
     */
    public function up(): void
    {
        Schema::table('sponsored_listings', function (Blueprint $table) {
            $table->string('jeko_payment_id')->nullable()->after('payment_reference')
                ->comment('Jeko payment request ID for status tracking');
            $table->string('jeko_reference')->nullable()->after('jeko_payment_id')
                ->comment('Unique reference sent to Jeko');
            $table->string('payment_method')->nullable()->after('jeko_reference')
                ->comment('wave, orange, mtn, moov, djamo');
            $table->string('payment_status')->default('pending')->after('payment_method')
                ->comment('pending, processing, success, error');
            $table->timestamp('paid_at')->nullable()->after('payment_status')
                ->comment('When the payment was confirmed');

            $table->index('jeko_payment_id');
            $table->index('jeko_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sponsored_listings', function (Blueprint $table) {
            $table->dropIndex(['jeko_payment_id']);
            $table->dropIndex(['jeko_reference']);
            $table->dropColumn([
                'jeko_payment_id',
                'jeko_reference',
                'payment_method',
                'payment_status',
                'paid_at',
            ]);
        });
    }
};
