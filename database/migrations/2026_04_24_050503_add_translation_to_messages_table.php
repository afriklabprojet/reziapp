<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'original_locale')) {
                $table->string('original_locale', 5)->nullable()->after('content');
            }
            if (!Schema::hasColumn('messages', 'translated_content')) {
                $table->text('translated_content')->nullable()->after('original_locale');
            }
            if (!Schema::hasColumn('messages', 'translated_locale')) {
                $table->string('translated_locale', 5)->nullable()->after('translated_content');
            }
            if (!Schema::hasColumn('messages', 'translated_at')) {
                $table->timestamp('translated_at')->nullable()->after('translated_locale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['original_locale', 'translated_content', 'translated_locale', 'translated_at']);
        });
    }
};
