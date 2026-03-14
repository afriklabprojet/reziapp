<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Contacts d'urgence et mode discret
     */
    public function up(): void
    {
        // Contacts d'urgence
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->string('phone', 20);
            $table->string('relationship')->nullable(); // Famille, ami, etc.
            $table->string('email')->nullable();

            $table->boolean('is_primary')->default(false);
            $table->boolean('notify_on_emergency')->default(true);
            $table->boolean('share_location')->default(false); // Partager localisation en cas d'urgence

            $table->timestamps();

            $table->index('user_id');
        });

        // Alertes d'urgence
        Schema::create('emergency_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Localisation au moment de l'alerte
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('address')->nullable();

            // Type d'alerte
            $table->enum('alert_type', [
                'panic',            // Bouton panique
                'sos',              // SOS
                'check_in_missed',  // Check-in manqué
                'suspicious',       // Situation suspecte
                'medical',          // Urgence médicale
                'other',
            ])->default('panic');

            $table->text('message')->nullable();
            $table->json('context')->nullable(); // Contexte: résidence visitée, RDV prévu, etc.

            // Statut
            $table->enum('status', [
                'triggered',    // Déclenché
                'notified',     // Contacts notifiés
                'acknowledged', // Pris en charge
                'resolved',     // Résolu
                'false_alarm',   // Fausse alerte
            ])->default('triggered');

            // Notifications envoyées
            $table->json('notifications_sent')->nullable();
            $table->timestamp('contacts_notified_at')->nullable();

            // Résolution
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });

        // Ajout colonnes sécurité sur users
        Schema::table('users', function (Blueprint $table) {
            // Vérifications
            $table->boolean('email_verified')->default(false)->after('email_verified_at');
            $table->boolean('phone_verified')->default(false)->after('email_verified');
            $table->boolean('identity_verified')->default(false)->after('phone_verified');
            $table->enum('verification_level', ['none', 'basic', 'standard', 'premium', 'trusted'])
                  ->default('none')->after('identity_verified');

            // Sécurité
            $table->boolean('is_suspended')->default(false)->after('verification_level');
            $table->timestamp('suspended_until')->nullable()->after('is_suspended');
            $table->text('suspension_reason')->nullable()->after('suspended_until');

            // Mode discret
            $table->boolean('discrete_mode')->default(false)->after('suspension_reason');
            $table->boolean('emergency_mode')->default(false)->after('discrete_mode');

            // 2FA
            $table->boolean('two_factor_enabled')->default(false)->after('emergency_mode');
            $table->string('two_factor_secret')->nullable()->after('two_factor_enabled');

            // Dernière activité de sécurité
            $table->timestamp('last_security_check')->nullable()->after('two_factor_secret');
            $table->string('last_login_ip')->nullable()->after('last_security_check');
            $table->timestamp('last_login_at')->nullable()->after('last_login_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verified',
                'phone_verified',
                'identity_verified',
                'verification_level',
                'is_suspended',
                'suspended_until',
                'suspension_reason',
                'discrete_mode',
                'emergency_mode',
                'two_factor_enabled',
                'two_factor_secret',
                'last_security_check',
                'last_login_ip',
                'last_login_at',
            ]);
        });

        Schema::dropIfExists('emergency_alerts');
        Schema::dropIfExists('emergency_contacts');
    }
};
