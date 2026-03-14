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
            $table->boolean('is_suspended')->default(false)->after('is_available');
            $table->string('suspension_reason')->nullable()->after('is_suspended');
            $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
            $table->timestamp('resume_at')->nullable()->after('suspended_at');
            $table->text('suspension_note')->nullable()->after('resume_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->dropColumn([
                'is_suspended',
                'suspension_reason',
                'suspended_at',
                'resume_at',
                'suspension_note',
            ]);
        });
    }
};
