<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // ═══════════════════════════════════════════
        // 1. CHARGES & DÉPENSES
        // ═══════════════════════════════════════════
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // water, electricity, maintenance, tax, insurance, other
            $table->string('label');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('XOF');
            $table->date('expense_date');
            $table->string('receipt_path')->nullable(); // justificatif
            $table->text('notes')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_frequency')->nullable(); // monthly, quarterly, yearly
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'residence_id', 'expense_date']);
            $table->index(['category']);
        });

        // ═══════════════════════════════════════════
        // 2. RELANCES LOYER
        // ═══════════════════════════════════════════
        Schema::create('rent_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lease_contract_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount_due', 12, 2);
            $table->string('currency', 3)->default('XOF');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->string('status')->default('pending'); // pending, sent, paid, overdue
            $table->string('reminder_level')->default('none'); // none, j5, j3, j1, overdue, escalated
            $table->timestamp('last_reminder_at')->nullable();
            $table->unsignedTinyInteger('reminder_count')->default(0);
            $table->string('channel')->nullable(); // email, sms, whatsapp
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
            $table->index(['tenant_id', 'due_date']);
            $table->index(['due_date', 'status']);
        });

        // ═══════════════════════════════════════════
        // 3. CHECK-IN / CHECK-OUT DIGITAL
        // ═══════════════════════════════════════════
        Schema::create('digital_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // check_in, check_out
            $table->string('qr_token', 64)->unique();
            $table->string('status')->default('pending'); // pending, confirmed, completed
            $table->timestamp('confirmed_at')->nullable();
            $table->string('confirmed_by')->nullable(); // guest, owner, auto
            $table->json('arrival_instructions')->nullable(); // door_code, wifi, guardian_phone, etc.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->json('photos')->nullable(); // Photos prises au check-in/out
            $table->timestamps();

            $table->index(['booking_id', 'type']);
            $table->index(['qr_token']);
        });

        // ═══════════════════════════════════════════
        // 4. MAINTENANCE & INCIDENTS
        // ═══════════════════════════════════════════
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete(); // locataire
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // prestataire
            $table->string('category'); // plumbing, electrical, appliance, structural, cleaning, other
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->string('title');
            $table->text('description');
            $table->json('photos')->nullable();
            $table->string('status')->default('reported'); // reported, acknowledged, in_progress, resolved, closed
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->unsignedTinyInteger('satisfaction_rating')->nullable(); // 1-5
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status']);
            $table->index(['residence_id', 'status']);
            $table->index(['reported_by']);
        });

        // ═══════════════════════════════════════════
        // 5. DOCUMENTS & ARCHIVAGE
        // ═══════════════════════════════════════════
        Schema::create('owner_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('residence_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category'); // title_deed, permit, insurance, tax, contract, other
            $table->string('name');
            $table->string('file_path');
            $table->string('file_type', 10)->nullable(); // pdf, jpg, png
            $table->unsignedInteger('file_size')->nullable(); // bytes
            $table->date('expiry_date')->nullable();
            $table->boolean('expiry_notified')->default(false);
            $table->text('notes')->nullable();
            $table->json('shared_with')->nullable(); // user IDs
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'category']);
            $table->index(['expiry_date']);
        });

        // ═══════════════════════════════════════════
        // 6. GESTION MÉNAGE / TURNOVER
        // ═══════════════════════════════════════════
        Schema::create('cleaning_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, in_progress, completed, verified
            $table->string('priority')->default('normal'); // normal, urgent
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->unsignedSmallInteger('estimated_duration')->nullable(); // minutes
            $table->json('checklist')->nullable(); // [{item, done}]
            $table->text('special_instructions')->nullable();
            $table->json('before_photos')->nullable();
            $table->json('after_photos')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
            $table->index(['residence_id', 'scheduled_date']);
            $table->index(['assigned_to', 'status']);
        });

        // ═══════════════════════════════════════════
        // 7. AVIS PROPRIÉTAIRE SUR LOCATAIRE
        // ═══════════════════════════════════════════
        Schema::create('tenant_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lease_contract_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('cleanliness_rating'); // 1-5
            $table->unsignedTinyInteger('respect_rules_rating'); // 1-5
            $table->unsignedTinyInteger('communication_rating'); // 1-5
            $table->unsignedTinyInteger('payment_rating'); // 1-5
            $table->unsignedTinyInteger('overall_rating'); // 1-5
            $table->text('comment')->nullable();
            $table->boolean('would_rent_again')->default(true);
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->unique(['owner_id', 'tenant_id', 'booking_id']);
            $table->index(['tenant_id', 'overall_rating']);
        });

        // ═══════════════════════════════════════════
        // 8. MODE VACANCES
        // ═══════════════════════════════════════════
        Schema::create('vacation_modes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->text('auto_message')->nullable(); // message aux prospects
            $table->boolean('is_active')->default(true);
            $table->json('affected_residences')->nullable(); // null = toutes
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });

        // ═══════════════════════════════════════════
        // 9. ASSURANCE - compléter le modèle existant
        // ═══════════════════════════════════════════
        if (!Schema::hasTable('insurance_subscriptions')) {
            Schema::create('insurance_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
                $table->foreignId('insurance_plan_id')->nullable()->constrained()->nullOnDelete();
                $table->string('provider'); // name of insurance company
                $table->string('policy_number')->nullable();
                $table->string('coverage_type'); // basic, standard, premium
                $table->decimal('monthly_premium', 10, 2);
                $table->string('currency', 3)->default('XOF');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status')->default('active'); // active, expired, cancelled
                $table->json('coverage_details')->nullable();
                $table->timestamps();

                $table->index(['owner_id', 'status']);
                $table->index(['residence_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_subscriptions');
        Schema::dropIfExists('vacation_modes');
        Schema::dropIfExists('tenant_reviews');
        Schema::dropIfExists('cleaning_tasks');
        Schema::dropIfExists('owner_documents');
        Schema::dropIfExists('maintenance_requests');
        Schema::dropIfExists('digital_checkins');
        Schema::dropIfExists('rent_reminders');
        Schema::dropIfExists('expenses');
    }
};
