<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'payment_split')) {
                $table->boolean('payment_split')->default(false)->after('payment_method');
            }
            if (!Schema::hasColumn('bookings', 'deposit_amount')) {
                $table->decimal('deposit_amount', 12, 2)->nullable()->after('payment_split');
            }
            if (!Schema::hasColumn('bookings', 'deposit_paid_at')) {
                $table->timestamp('deposit_paid_at')->nullable()->after('deposit_amount');
            }
            if (!Schema::hasColumn('bookings', 'balance_amount')) {
                $table->decimal('balance_amount', 12, 2)->nullable()->after('deposit_paid_at');
            }
            if (!Schema::hasColumn('bookings', 'balance_due_at')) {
                $table->date('balance_due_at')->nullable()->after('balance_amount');
            }
            if (!Schema::hasColumn('bookings', 'balance_paid_at')) {
                $table->timestamp('balance_paid_at')->nullable()->after('balance_due_at');
            }
            if (!Schema::hasColumn('bookings', 'balance_reminder_sent_at')) {
                $table->timestamp('balance_reminder_sent_at')->nullable()->after('balance_paid_at');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'balance_due_at_idx_check')) {
                // index for daily scheduled charge
                $table->index(['balance_due_at', 'balance_paid_at'], 'bookings_balance_due_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_balance_due_idx');
            $table->dropColumn([
                'payment_split', 'deposit_amount', 'deposit_paid_at',
                'balance_amount', 'balance_due_at', 'balance_paid_at',
                'balance_reminder_sent_at',
            ]);
        });
    }
};
