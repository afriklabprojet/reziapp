<?php

namespace Database\Seeders;

use App\Models\Residence;
use App\Models\SponsoredListing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SponsoredListingSeeder extends Seeder
{
    public function run(): void
    {
        $owners   = User::where('role', 'owner')->get()->keyBy('id');
        $residences = Residence::where('status', 'active')->with('owner')->get();

        if ($residences->isEmpty()) {
            $this->command->warn('⚠️  Aucune résidence active.');
            return;
        }

        $res = $residences->values();

        $listings = [
            // ── Actives (payées + dates valides) ──────────────────────────
            [
                'residence'      => $res[0] ?? null,
                'type'           => 'featured_home',
                'duration_days'  => 30,
                'total_budget'   => 100000.00,
                'amount_spent'   => 64500.00,
                'billing_type'   => 'flat_rate',
                'cost_per_unit'  => 0,
                'impressions'    => 1842,
                'clicks'         => 67,
                'contacts_generated' => 12,
                'status'         => 'active',
                'is_paid'        => true,
                'payment_method' => 'wave',
                'payment_status' => 'success',
                'payment_reference' => 'WAVE-SP-001',
                'starts_at'      => Carbon::now()->subDays(14),
                'ends_at'        => Carbon::now()->addDays(16),
                'paid_at'        => Carbon::now()->subDays(14),
            ],
            [
                'residence'      => $res[1] ?? null,
                'type'           => 'top_search',
                'duration_days'  => 14,
                'total_budget'   => 42000.00,
                'amount_spent'   => 38500.00,
                'billing_type'   => 'per_click',
                'cost_per_unit'  => 50,
                'impressions'    => 3210,
                'clicks'         => 154,
                'contacts_generated' => 28,
                'status'         => 'active',
                'is_paid'        => true,
                'payment_method' => 'orange',
                'payment_status' => 'success',
                'payment_reference' => 'ORANGE-SP-002',
                'starts_at'      => Carbon::now()->subDays(7),
                'ends_at'        => Carbon::now()->addDays(7),
                'paid_at'        => Carbon::now()->subDays(7),
            ],
            [
                'residence'      => $res[2] ?? $res[0] ?? null,
                'type'           => 'highlighted',
                'duration_days'  => 7,
                'total_budget'   => 7500.00,
                'amount_spent'   => 5500.00,
                'billing_type'   => 'flat_rate',
                'cost_per_unit'  => 0,
                'impressions'    => 620,
                'clicks'         => 22,
                'contacts_generated' => 5,
                'status'         => 'active',
                'is_paid'        => true,
                'payment_method' => 'mtn',
                'payment_status' => 'success',
                'payment_reference' => 'MTN-SP-003',
                'starts_at'      => Carbon::now()->subDays(3),
                'ends_at'        => Carbon::now()->addDays(4),
                'paid_at'        => Carbon::now()->subDays(3),
            ],
            // ── Terminée (historique) ──────────────────────────────────────
            [
                'residence'      => $res[3] ?? $res[0] ?? null,
                'type'           => 'premium_listing',
                'duration_days'  => 30,
                'total_budget'   => 105000.00,
                'amount_spent'   => 105000.00,
                'billing_type'   => 'flat_rate',
                'cost_per_unit'  => 0,
                'impressions'    => 5480,
                'clicks'         => 201,
                'contacts_generated' => 47,
                'status'         => 'completed',
                'is_paid'        => true,
                'payment_method' => 'wave',
                'payment_status' => 'success',
                'payment_reference' => 'WAVE-SP-004',
                'starts_at'      => Carbon::now()->subDays(45),
                'ends_at'        => Carbon::now()->subDays(15),
                'paid_at'        => Carbon::now()->subDays(45),
            ],
            // ── En attente de paiement ─────────────────────────────────────
            [
                'residence'      => $res[4] ?? $res[0] ?? null,
                'type'           => 'featured_home',
                'duration_days'  => 14,
                'total_budget'   => 50000.00,
                'amount_spent'   => 0,
                'billing_type'   => 'flat_rate',
                'cost_per_unit'  => 0,
                'impressions'    => 0,
                'clicks'         => 0,
                'contacts_generated' => 0,
                'status'         => 'pending',
                'is_paid'        => false,
                'payment_method' => null,
                'payment_status' => 'pending',
                'payment_reference' => null,
                'starts_at'      => null,
                'ends_at'        => null,
                'paid_at'        => null,
            ],
            // ── En pause ──────────────────────────────────────────────────
            [
                'residence'      => $res[1] ?? $res[0] ?? null,
                'type'           => 'highlighted',
                'duration_days'  => 14,
                'total_budget'   => 21000.00,
                'amount_spent'   => 9800.00,
                'billing_type'   => 'flat_rate',
                'cost_per_unit'  => 0,
                'impressions'    => 890,
                'clicks'         => 33,
                'contacts_generated' => 8,
                'status'         => 'paused',
                'is_paid'        => true,
                'payment_method' => 'djamo',
                'payment_status' => 'success',
                'payment_reference' => 'DJAMO-SP-006',
                'starts_at'      => Carbon::now()->subDays(10),
                'ends_at'        => Carbon::now()->addDays(4),
                'paid_at'        => Carbon::now()->subDays(10),
            ],
        ];

        foreach ($listings as $data) {
            $residence = $data['residence'];
            if (! $residence) continue;

            SponsoredListing::create([
                'residence_id'       => $residence->id,
                'user_id'            => $residence->owner_id,
                'type'               => $data['type'],
                'starts_at'          => $data['starts_at'],
                'ends_at'            => $data['ends_at'],
                'duration_days'      => $data['duration_days'],
                'position'           => 1,
                'daily_budget'       => null,
                'total_budget'       => $data['total_budget'],
                'amount_spent'       => $data['amount_spent'],
                'billing_type'       => $data['billing_type'],
                'cost_per_unit'      => $data['cost_per_unit'],
                'impressions'        => $data['impressions'],
                'clicks'             => $data['clicks'],
                'contacts_generated' => $data['contacts_generated'],
                'target_communes'    => null,
                'target_user_types'  => null,
                'status'             => $data['status'],
                'is_paid'            => $data['is_paid'],
                'payment_reference'  => $data['payment_reference'],
                'payment_method'     => $data['payment_method'],
                'payment_status'     => $data['payment_status'],
                'paid_at'            => $data['paid_at'],
                'created_at'         => $data['starts_at'] ?? Carbon::now()->subDays(rand(1, 30)),
                'updated_at'         => Carbon::now()->subDays(rand(0, 2)),
            ]);
        }

        $this->command->info('✅ SponsoredListings seeded: '.SponsoredListing::count().' listings.');
    }
}
