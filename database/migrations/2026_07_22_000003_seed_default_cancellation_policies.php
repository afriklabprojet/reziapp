<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 1 — Annulation gratuite 48h par défaut (Booking-killer).
 *
 * Insère 3 politiques de référence si la table est vide :
 *   - flexible_48h : 100% remboursement jusqu'à 48h avant check-in (DEFAUT)
 *   - moderate     : 100% jusqu'à 5 jours, 50% ensuite
 *   - strict       : 50% jusqu'à 7 jours, 0% ensuite
 *
 * Si la table contient déjà des politiques, on garantit juste qu'une `flexible_48h`
 * existe et qu'elle est marquée comme `is_default`.
 */
return new class () extends Migration {
    public function up(): void
    {
        $now = now();

        $flexibleRules = [
            ['days_before' => 2, 'refund_percent' => 100],
            ['days_before' => 1, 'refund_percent' => 50],
            ['days_before' => 0, 'refund_percent' => 0],
        ];

        $moderateRules = [
            ['days_before' => 5, 'refund_percent' => 100],
            ['days_before' => 1, 'refund_percent' => 50],
            ['days_before' => 0, 'refund_percent' => 0],
        ];

        $strictRules = [
            ['days_before' => 7, 'refund_percent' => 50],
            ['days_before' => 0, 'refund_percent' => 0],
        ];

        // Reset is_default flag (un seul default à la fois)
        DB::table('cancellation_policies')->where('is_default', true)->update(['is_default' => false]);

        $this->upsertPolicy([
            'name' => 'flexible_48h',
            'display_name' => 'Annulation gratuite 48h',
            'description' => 'Remboursement intégral jusqu\'à 48h avant l\'arrivée. Au-delà, 50% jusqu\'à 24h, puis 0%.',
            'refund_rules' => json_encode($flexibleRules),
            'service_fee_refundable_percent' => 100,
            'owner_cancellation_refund_percent' => 100,
            'owner_cancellation_penalty_percent' => 10,
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->upsertPolicy([
            'name' => 'moderate',
            'display_name' => 'Modérée',
            'description' => 'Remboursement intégral jusqu\'à 5 jours avant. 50% jusqu\'à 24h. 0% ensuite.',
            'refund_rules' => json_encode($moderateRules),
            'service_fee_refundable_percent' => 100,
            'owner_cancellation_refund_percent' => 100,
            'owner_cancellation_penalty_percent' => 10,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->upsertPolicy([
            'name' => 'strict',
            'display_name' => 'Stricte',
            'description' => '50% remboursé jusqu\'à 7 jours avant. 0% ensuite.',
            'refund_rules' => json_encode($strictRules),
            'service_fee_refundable_percent' => 0,
            'owner_cancellation_refund_percent' => 100,
            'owner_cancellation_penalty_percent' => 20,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 3,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Garde les politiques (don't break bookings) — on retire juste le default flag.
        DB::table('cancellation_policies')
            ->where('name', 'flexible_48h')
            ->update(['is_default' => false]);
    }

    private function upsertPolicy(array $data): void
    {
        $existing = DB::table('cancellation_policies')->where('name', $data['name'])->first();

        if ($existing) {
            DB::table('cancellation_policies')
                ->where('id', $existing->id)
                ->update([
                    'display_name' => $data['display_name'],
                    'description'  => $data['description'],
                    'refund_rules' => $data['refund_rules'],
                    'is_active'    => $data['is_active'],
                    'is_default'   => $data['is_default'],
                    'sort_order'   => $data['sort_order'],
                    'updated_at'   => $data['updated_at'],
                ]);
        } else {
            DB::table('cancellation_policies')->insert($data);
        }
    }
};
