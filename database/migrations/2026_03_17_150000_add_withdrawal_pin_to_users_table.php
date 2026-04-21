<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('withdrawal_pin')->nullable()->after('jeko_contact_id');
            $table->timestamp('withdrawal_pin_set_at')->nullable()->after('withdrawal_pin');
            $table->unsignedTinyInteger('withdrawal_pin_attempts')->default(0)->after('withdrawal_pin_set_at');
            $table->timestamp('withdrawal_pin_locked_until')->nullable()->after('withdrawal_pin_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'withdrawal_pin',
                'withdrawal_pin_set_at',
                'withdrawal_pin_attempts',
                'withdrawal_pin_locked_until',
            ]);
        });
    }
};
