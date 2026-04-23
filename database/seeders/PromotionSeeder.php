<?php

namespace Database\Seeders;

use App\Models\Promotion;
use App\Models\Residence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $owners     = User::where('role', 'owner')->get();
        $residences = Residence::where('status', 'active')->get();

        if ($owners->isEmpty() || $residences->isEmpty()) {
            $this->command->warn('⚠️  Aucun owner ou résidence active trouvé.');

            return;
        }

        $titles = [
            'Offre Spéciale Weekend',
            'Réduction Séjour Long',
            'Flash Sale -30%',
            'Promo Saison Haute',
            '1 nuit offerte dès 7 nuits',
            'Tarif Préférentiel Étudiant',
            'Offre Découverte',
            'Promotion Fidélité',
            'Réduction Réservation Anticipée',
            'Offre Dernière Minute',
        ];

        // ─── Promotions actives (en cours) ──────────────────────────────────
        $this->command->info('  → 8 promotions actives...');
        for ($i = 0; $i < 8; $i++) {
            $daysAgo  = rand(0, 20);
            $duration = rand(14, 45);
            $type     = ['percentage', 'fixed', 'free_nights'][rand(0, 2)];
            $owner    = $owners->random();
            $residence = $residences->where('owner_id', $owner->id)->isNotEmpty()
                ? $residences->where('owner_id', $owner->id)->random()
                : $residences->random();

            Promotion::create([
                'residence_id'    => $residence->id,
                'user_id'         => $owner->id,
                'title'           => $titles[array_rand($titles)],
                'description'     => 'Profitez de cette offre limitée sur notre résidence. Réservez maintenant !',
                'discount_type'   => $type,
                'discount_value'  => $type === 'percentage' ? rand(10, 40) : ($type === 'fixed' ? rand(5000, 25000) : 1),
                'free_nights_min' => $type === 'free_nights' ? rand(5, 7) : null,
                'starts_at'       => Carbon::now()->subDays($daysAgo),
                'ends_at'         => Carbon::now()->subDays($daysAgo)->addDays($duration),
                'min_nights'      => rand(2, 5),
                'max_uses'        => rand(0, 1) ? rand(5, 50) : null,
                'uses_count'      => rand(0, 10),
                'is_active'       => true,
                'is_featured'     => rand(0, 1) === 1,
                'created_at'      => Carbon::now()->subDays($daysAgo + 1),
                'updated_at'      => Carbon::now()->subDays(rand(0, $daysAgo ?: 1)),
            ]);
        }

        // ─── Promotions expirées (historique) ───────────────────────────────
        $this->command->info('  → 12 promotions expirées...');
        for ($i = 0; $i < 12; $i++) {
            $daysAgo  = rand(30, 120);
            $duration = rand(7, 30);
            $type     = ['percentage', 'fixed', 'free_nights'][rand(0, 2)];
            $owner    = $owners->random();
            $residence = $residences->random();
            $uses     = rand(3, 30);

            Promotion::create([
                'residence_id'    => $residence->id,
                'user_id'         => $owner->id,
                'title'           => $titles[array_rand($titles)],
                'description'     => 'Offre expirée — résidence disponible à tarif normal.',
                'discount_type'   => $type,
                'discount_value'  => $type === 'percentage' ? rand(10, 35) : ($type === 'fixed' ? rand(5000, 20000) : 1),
                'free_nights_min' => $type === 'free_nights' ? rand(5, 7) : null,
                'starts_at'       => Carbon::now()->subDays($daysAgo + $duration),
                'ends_at'         => Carbon::now()->subDays($daysAgo),
                'min_nights'      => rand(2, 5),
                'max_uses'        => $uses + rand(0, 20),
                'uses_count'      => $uses,
                'is_active'       => false,
                'is_featured'     => false,
                'created_at'      => Carbon::now()->subDays($daysAgo + $duration + 1),
                'updated_at'      => Carbon::now()->subDays($daysAgo),
            ]);
        }

        // ─── Promotions à venir (programmées) ───────────────────────────────
        $this->command->info('  → 4 promotions programmées...');
        for ($i = 0; $i < 4; $i++) {
            $daysAhead = rand(3, 20);
            $duration  = rand(7, 21);
            $type      = ['percentage', 'fixed'][rand(0, 1)];
            $owner     = $owners->random();
            $residence = $residences->where('owner_id', $owner->id)->isNotEmpty()
                ? $residences->where('owner_id', $owner->id)->random()
                : $residences->random();

            Promotion::create([
                'residence_id'    => $residence->id,
                'user_id'         => $owner->id,
                'title'           => $titles[array_rand($titles)],
                'description'     => 'Promotion à venir — réservez dès maintenant pour bénéficier du tarif réduit.',
                'discount_type'   => $type,
                'discount_value'  => $type === 'percentage' ? rand(15, 40) : rand(8000, 30000),
                'free_nights_min' => null,
                'starts_at'       => Carbon::now()->addDays($daysAhead),
                'ends_at'         => Carbon::now()->addDays($daysAhead + $duration),
                'min_nights'      => rand(2, 4),
                'max_uses'        => rand(10, 30),
                'uses_count'      => 0,
                'is_active'       => true,
                'is_featured'     => rand(0, 1) === 1,
                'created_at'      => now()->subDays(rand(1, 5)),
                'updated_at'      => now()->subDays(rand(0, 3)),
            ]);
        }

        $this->command->info('✅ 24 promotions créées (8 actives + 12 expirées + 4 programmées)');
    }
}
