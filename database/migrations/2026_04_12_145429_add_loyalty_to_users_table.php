<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Programme de fidélité style Genius (Booking.com)
            $table->enum('loyalty_tier', ['standard', 'bronze', 'silver', 'gold', 'platinum'])
                ->default('standard')
                ->after('wallet_credit');
            $table->unsignedInteger('loyalty_points')->default(0)->after('loyalty_tier');
            $table->unsignedSmallInteger('loyalty_bookings_count')->default(0)->after('loyalty_points');
            $table->unsignedSmallInteger('loyalty_nights_count')->default(0)->after('loyalty_bookings_count');
            $table->decimal('loyalty_total_spent', 12, 2)->default(0)->after('loyalty_nights_count');
            $table->timestamp('loyalty_tier_upgraded_at')->nullable()->after('loyalty_total_spent');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'loyalty_tier',
                'loyalty_points',
                'loyalty_bookings_count',
                'loyalty_nights_count',
                'loyalty_total_spent',
                'loyalty_tier_upgraded_at',
            ]);
        });
    }
};
