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
        Schema::table('residences', function (Blueprint $table) {
            // Champs de modération
            if (!Schema::hasColumn('residences', 'rejection_reason')) {
                $table->string('rejection_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('residences', 'rejection_details')) {
                $table->text('rejection_details')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('residences', 'changes_requested')) {
                $table->json('changes_requested')->nullable()->after('rejection_details');
            }
            if (!Schema::hasColumn('residences', 'change_message')) {
                $table->text('change_message')->nullable()->after('changes_requested');
            }
            if (!Schema::hasColumn('residences', 'moderated_by')) {
                $table->foreignId('moderated_by')->nullable()->after('change_message')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('residences', 'moderated_at')) {
                $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropForeign(['moderated_by']);
            $table->dropColumn([
                'rejection_reason',
                'rejection_details',
                'changes_requested',
                'change_message',
                'moderated_by',
                'moderated_at',
            ]);
        });
    }
};
