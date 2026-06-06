<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     * Module Réservation complet - Extensions
     */
    public function up(): void
    {
        // Codes promotionnels
        if (!Schema::hasTable('promo_codes')) {
            Schema::create('promo_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('value', 10, 2);
                $table->decimal('min_amount', 12, 2)->nullable();
                $table->decimal('max_discount', 12, 2)->nullable();
                $table->integer('min_nights')->nullable();
                $table->integer('max_uses')->nullable();
                $table->integer('max_uses_per_user')->default(1);
                $table->integer('uses_count')->default(0);
                $table->foreignId('residence_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('first_booking_only')->default(false);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['code', 'is_active']);
            });
        }

        // Réductions long séjour
        if (!Schema::hasTable('long_stay_discounts')) {
            Schema::create('long_stay_discounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('residence_id')->constrained()->onDelete('cascade');
                $table->integer('min_nights');
                $table->decimal('discount_percentage', 5, 2);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['residence_id', 'min_nights']);
            });
        }

        // Prix spéciaux par date
        if (!Schema::hasTable('special_prices')) {
            Schema::create('special_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('residence_id')->constrained()->onDelete('cascade');
                $table->date('date');
                $table->decimal('price', 12, 2);
                $table->string('reason')->nullable();
                $table->timestamps();

                $table->unique(['residence_id', 'date']);
            });
        }

        // Blocages de dates
        if (!Schema::hasTable('blocked_dates')) {
            Schema::create('blocked_dates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('residence_id')->constrained()->onDelete('cascade');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('reason')->nullable();
                $table->timestamps();

                $table->index(['residence_id', 'start_date', 'end_date']);
            });
        }

        // Ajouter colonnes manquantes à bookings (en utilisant les bonnes colonnes de référence)
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (!Schema::hasColumn('bookings', 'promo_code_id')) {
                $table->foreignId('promo_code_id')->nullable()->after('cancellation_policy_id');
            }
            if (!Schema::hasColumn('bookings', 'check_in_time')) {
                $table->time('check_in_time')->default('14:00')->after('check_out');
            }
            if (!Schema::hasColumn('bookings', 'check_out_time')) {
                $table->time('check_out_time')->default('11:00')->after('check_in_time');
            }
            if (!Schema::hasColumn('bookings', 'adults')) {
                $table->integer('adults')->default(1)->after('guests');
            }
            if (!Schema::hasColumn('bookings', 'children')) {
                $table->integer('children')->default(0)->after('adults');
            }
            if (!Schema::hasColumn('bookings', 'infants')) {
                $table->integer('infants')->default(0)->after('children');
            }
            if (!Schema::hasColumn('bookings', 'booking_type')) {
                $table->string('booking_type')->default('instant')->after('infants');
            }
            if (!Schema::hasColumn('bookings', 'long_stay_discount')) {
                $table->decimal('long_stay_discount', 10, 2)->default(0)->after('service_fee');
            }
            if (!Schema::hasColumn('bookings', 'promo_discount')) {
                $table->decimal('promo_discount', 10, 2)->default(0)->after('long_stay_discount');
            }
            if (!Schema::hasColumn('bookings', 'total_discount')) {
                $table->decimal('total_discount', 10, 2)->default(0)->after('promo_discount');
            }
            if (!Schema::hasColumn('bookings', 'currency')) {
                $table->string('currency')->default('XOF')->after('total_amount');
            }
            if (!Schema::hasColumn('bookings', 'price_breakdown')) {
                $table->json('price_breakdown')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('bookings', 'security_deposit')) {
                $table->decimal('security_deposit', 12, 2)->default(0)->after('price_breakdown');
            }
            if (!Schema::hasColumn('bookings', 'deposit_status')) {
                $table->string('deposit_status')->nullable()->after('security_deposit');
            }
            if (!Schema::hasColumn('bookings', 'preauth_id')) {
                $table->string('preauth_id')->nullable()->after('deposit_status');
            }
            if (!Schema::hasColumn('bookings', 'preauth_amount')) {
                $table->decimal('preauth_amount', 12, 2)->nullable()->after('preauth_id');
            }
            if (!Schema::hasColumn('bookings', 'preauth_expires_at')) {
                $table->timestamp('preauth_expires_at')->nullable()->after('preauth_amount');
            }
            if (!Schema::hasColumn('bookings', 'preauth_status')) {
                $table->string('preauth_status')->nullable()->after('preauth_expires_at');
            }
            if (!Schema::hasColumn('bookings', 'host_notes')) {
                $table->text('host_notes')->nullable()->after('owner_notes');
            }
            if (!Schema::hasColumn('bookings', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('host_notes');
            }
            if (!Schema::hasColumn('bookings', 'requested_check_in_time')) {
                $table->time('requested_check_in_time')->nullable()->after('internal_notes');
            }
            if (!Schema::hasColumn('bookings', 'requested_check_out_time')) {
                $table->time('requested_check_out_time')->nullable()->after('requested_check_in_time');
            }
            if (!Schema::hasColumn('bookings', 'special_requests')) {
                $table->json('special_requests')->nullable()->after('requested_check_out_time');
            }
            if (!Schema::hasColumn('bookings', 'requested_at')) {
                $table->timestamp('requested_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'declined_at')) {
                $table->timestamp('declined_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'checked_out_at')) {
                $table->timestamp('checked_out_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
        });

        // Demandes de réservation
        if (!Schema::hasTable('booking_requests')) {
            Schema::create('booking_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->onDelete('cascade');
                $table->string('action');
                $table->foreignId('action_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('message')->nullable();
                $table->text('reason')->nullable();
                $table->json('changes')->nullable();
                $table->timestamps();

                $table->index(['booking_id', 'action']);
            });
        }

        // Utilisations des codes promo
        if (!Schema::hasTable('promo_code_uses')) {
            Schema::create('promo_code_uses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('promo_code_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('booking_id')->constrained()->onDelete('cascade');
                $table->decimal('discount_amount', 12, 2);
                $table->timestamps();

                $table->unique(['promo_code_id', 'booking_id']);
            });
        }

        // Créneaux horaires
        if (!Schema::hasTable('check_in_slots')) {
            Schema::create('check_in_slots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('residence_id')->constrained()->onDelete('cascade');
                $table->time('check_in_start')->default('14:00');
                $table->time('check_in_end')->default('22:00');
                $table->time('check_out_time')->default('11:00');
                $table->boolean('flexible_check_in')->default(false);
                $table->decimal('early_check_in_fee', 10, 2)->nullable();
                $table->decimal('late_check_out_fee', 10, 2)->nullable();
                $table->timestamps();
            });
        }

        // Seed des codes promo
        $this->seedPromoCodes();
    }

    private function seedPromoCodes(): void
    {
        if (\DB::table('promo_codes')->count() > 0) {
            return;
        }

        $codes = [
            [
                'code' => 'BIENVENUE10',
                'name' => 'Bienvenue sur ReziApp',
                'description' => '10% de réduction pour votre première réservation',
                'type' => 'percentage',
                'value' => 10,
                'max_discount' => 50000,
                'valid_until' => null,
                'first_booking_only' => 1,
                'is_active' => 1,
                'max_uses_per_user' => 1,
                'uses_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ReziApp2026',
                'name' => 'Offre 2026',
                'description' => '15% de réduction',
                'type' => 'percentage',
                'value' => 15,
                'max_discount' => 75000,
                'valid_until' => '2026-03-31',
                'first_booking_only' => 0,
                'is_active' => 1,
                'max_uses_per_user' => 1,
                'uses_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($codes as $code) {
            \DB::table('promo_codes')->insert($code);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('check_in_slots');
        Schema::dropIfExists('promo_code_uses');
        Schema::dropIfExists('booking_requests');
        Schema::dropIfExists('blocked_dates');
        Schema::dropIfExists('special_prices');
        Schema::dropIfExists('long_stay_discounts');
        Schema::dropIfExists('promo_codes');
    }
};
