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
        Schema::create('shared_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Propriétaire du document
            $table->foreignId('residence_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 255);
            $table->enum('type', ['rules', 'contract', 'plan', 'guide', 'invoice', 'receipt', 'other'])->default('other');
            $table->string('file_path');
            $table->string('file_type', 50); // pdf, jpg, png, doc, etc.
            $table->integer('file_size'); // en bytes
            $table->text('description')->nullable();
            $table->boolean('is_template')->default(false); // Document réutilisable
            $table->boolean('requires_signature')->default(false);
            $table->json('signed_by')->nullable(); // [{user_id, signed_at, signature_path}]
            $table->integer('view_count')->default(0);
            $table->integer('download_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'type']);
            $table->index(['residence_id', 'type']);
            $table->index(['conversation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_documents');
    }
};
