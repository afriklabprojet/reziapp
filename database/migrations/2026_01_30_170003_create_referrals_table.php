<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Programme de parrainage
     */
    public function up(): void
    {
        // Ajout du code de parrainage aux utilisateurs
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 20)->nullable()->unique()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'referred_by')) {
                $table->foreignId('referred_by')->nullable()->constrained('users')->onDelete('set null')->after('referral_code');
            }
            if (! Schema::hasColumn('users', 'referral_balance')) {
                $table->decimal('referral_balance', 10, 2)->default(0)->after('referred_by');
            }
        });

        // Table des parrainages
        if (! Schema::hasTable('referrals')) {
            Schema::create('referrals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('referred_id')->constrained('users')->onDelete('cascade');
                $table->enum('status', ['pending', 'qualified', 'rewarded', 'cancelled'])->default('pending');
                $table->datetime('qualified_at')->nullable();
                $table->datetime('rewarded_at')->nullable();
                $table->decimal('referrer_reward', 10, 2)->nullable();
                $table->decimal('referred_reward', 10, 2)->nullable();
                $table->string('reward_type')->default('credit');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['referrer_id', 'referred_id']);
            });
        }

        // Configuration du programme de parrainage
        if (! Schema::hasTable('referral_settings')) {
            Schema::create('referral_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_settings');
        Schema::dropIfExists('referrals');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn(['referral_code', 'referred_by', 'referral_balance']);
        });
    }
};
