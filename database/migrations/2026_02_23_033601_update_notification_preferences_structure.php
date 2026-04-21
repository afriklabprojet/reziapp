<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            // Ajouter les nouvelles colonnes par catégorie/canal
            if (!Schema::hasColumn('notification_preferences', 'messages_email')) {
                $table->boolean('messages_email')->default(true)->after('user_id');
                $table->boolean('messages_push')->default(true)->after('messages_email');
                $table->boolean('messages_sms')->default(false)->after('messages_push');
            }

            if (!Schema::hasColumn('notification_preferences', 'visits_email')) {
                $table->boolean('visits_email')->default(true)->after('messages_sms');
                $table->boolean('visits_push')->default(true)->after('visits_email');
                $table->boolean('visits_sms')->default(false)->after('visits_push');
            }

            if (!Schema::hasColumn('notification_preferences', 'payments_email')) {
                $table->boolean('payments_email')->default(true)->after('visits_sms');
                $table->boolean('payments_push')->default(true)->after('payments_email');
                $table->boolean('payments_sms')->default(true)->after('payments_push');
            }

            if (!Schema::hasColumn('notification_preferences', 'marketing_email')) {
                $table->boolean('marketing_email')->default(false)->after('payments_sms');
                $table->boolean('marketing_push')->default(false)->after('marketing_email');
                $table->boolean('marketing_sms')->default(false)->after('marketing_push');
            }

            if (!Schema::hasColumn('notification_preferences', 'security_email')) {
                $table->boolean('security_email')->default(true)->after('marketing_sms');
                $table->boolean('security_push')->default(true)->after('security_email');
                $table->boolean('security_sms')->default(true)->after('security_push');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $columns = [
                'messages_email', 'messages_push', 'messages_sms',
                'visits_email', 'visits_push', 'visits_sms',
                'payments_email', 'payments_push', 'payments_sms',
                'marketing_email', 'marketing_push', 'marketing_sms',
                'security_email', 'security_push', 'security_sms',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('notification_preferences', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
