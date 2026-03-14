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
            $table->string('referral_code', 20)->nullable()->unique()->after('remember_token');
            $table->foreignId('referred_by')->nullable()->constrained('users')->onDelete('set null')->after('referral_code');
            $table->decimal('referral_balance', 10, 2)->default(0)->after('referred_by'); // Solde de récompenses
        });

        // Table des parrainages
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade'); // Parrain
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade'); // Filleul

            $table->enum('status', ['pending', 'qualified', 'rewarded', 'cancelled'])->default('pending');
            // pending = inscrit, qualified = a fait une action qualifiante, rewarded = récompenses attribuées

            $table->datetime('qualified_at')->nullable(); // Date de qualification
            $table->datetime('rewarded_at')->nullable();

            // Récompenses
            $table->decimal('referrer_reward', 10, 2)->nullable(); // Récompense parrain
            $table->decimal('referred_reward', 10, 2)->nullable(); // Récompense filleul
            $table->string('reward_type')->default('credit'); // credit, coupon, discount

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['referrer_id', 'referred_id']);
        });

        // Configuration du programme de parrainage
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->timestamps();
        });
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
