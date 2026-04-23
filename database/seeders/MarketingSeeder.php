<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Coupon;
use App\Models\PromoCode;
use App\Models\Referral;
use App\Models\Residence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketingSeeder extends Seeder
{
    public function run(): void
    {
        $users   = User::where('role', 'user')->get();
        $owners  = User::where('role', 'owner')->get();
        $admin   = User::whereIn('role', ['admin', 'super_admin'])->first() ?? $owners->first();
        $residences = Residence::where('status', 'active')->get();

        if ($users->isEmpty()) {
            $this->command->warn('⚠️  Aucun utilisateur trouvé.');
            return;
        }

        $this->command->info('  → Referral settings...');
        $this->seedReferralSettings();

        $this->command->info('  → Parrainages (referrals)...');
        $this->seedReferrals($users);

        $this->command->info('  → Coupons...');
        $this->seedCoupons($admin, $residences, $owners);

        $this->command->info('  → Codes promo...');
        $this->seedPromoCodes($admin, $residences, $owners);

        $this->command->info('  → Campagnes marketing...');
        $this->seedCampaigns($admin);

        $this->command->info('✅ Marketing data seeded!');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REFERRAL SETTINGS
    // ──────────────────────────────────────────────────────────────────────────
    private function seedReferralSettings(): void
    {
        $settings = [
            'referral_enabled'          => '1',
            'referrer_reward_type'      => 'credit',
            'referrer_reward_amount'    => '5000',
            'referred_reward_type'      => 'discount',
            'referred_reward_amount'    => '3000',
            'min_booking_amount'        => '20000',
            'reward_delay_days'         => '7',
            'max_referrals_per_user'    => '20',
        ];

        foreach ($settings as $key => $value) {
            DB::table('referral_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REFERRALS (Parrainages)
    // ──────────────────────────────────────────────────────────────────────────
    private function seedReferrals($users): void
    {
        $usersArr = $users->values();
        $count    = min($usersArr->count() - 1, 6);

        // Récompensés (anciens)
        for ($i = 0; $i < min(4, $count); $i++) {
            $referrer = $usersArr[$i];
            $referred = $usersArr[$i + 1];
            if ($referrer->id === $referred->id) continue;

            $daysAgo = rand(20, 90);
            Referral::firstOrCreate(
                ['referrer_id' => $referrer->id, 'referred_id' => $referred->id],
                [
                    'status'          => 'rewarded',
                    'reward_type'     => 'credit',
                    'referrer_reward' => 5000.00,
                    'referred_reward' => 3000.00,
                    'qualified_at'    => Carbon::now()->subDays($daysAgo - 5),
                    'rewarded_at'     => Carbon::now()->subDays($daysAgo - 7),
                    'notes'           => 'Parrainage validé automatiquement après première réservation.',
                    'created_at'      => Carbon::now()->subDays($daysAgo),
                    'updated_at'      => Carbon::now()->subDays($daysAgo - 7),
                ]
            );
        }

        // Qualifiés (en attente de récompense)
        if ($usersArr->count() > $count + 1) {
            $referrer = $usersArr[$count];
            $referred = $usersArr->last();
            if ($referrer->id !== $referred->id) {
                Referral::firstOrCreate(
                    ['referrer_id' => $referrer->id, 'referred_id' => $referred->id],
                    [
                        'status'          => 'qualified',
                        'reward_type'     => 'credit',
                        'referrer_reward' => 5000.00,
                        'referred_reward' => 3000.00,
                        'qualified_at'    => Carbon::now()->subDays(3),
                        'rewarded_at'     => null,
                        'notes'           => null,
                        'created_at'      => Carbon::now()->subDays(10),
                        'updated_at'      => Carbon::now()->subDays(3),
                    ]
                );
            }
        }

        // Pending (inscription récente, pas encore réservé)
        if ($usersArr->count() > 2) {
            $referrer = $usersArr[0];
            $referred = $usersArr[2];
            if ($referrer->id !== $referred->id) {
                Referral::firstOrCreate(
                    ['referrer_id' => $referrer->id, 'referred_id' => $referred->id],
                    [
                        'status'      => 'pending',
                        'reward_type' => 'credit',
                        'notes'       => null,
                        'created_at'  => Carbon::now()->subDays(2),
                        'updated_at'  => Carbon::now()->subDays(2),
                    ]
                );
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // COUPONS
    // ──────────────────────────────────────────────────────────────────────────
    private function seedCoupons($admin, $residences, $owners): void
    {
        $coupons = [
            // Global / actifs
            [
                'code'              => 'BIENVENUE20',
                'name'              => 'Bienvenue -20%',
                'description'       => 'Réduction de bienvenue pour les nouveaux utilisateurs.',
                'discount_type'     => 'percentage',
                'discount_value'    => 20.00,
                'max_discount'      => 15000.00,
                'scope'             => 'global',
                'first_booking_only'=> 1,
                'max_uses'          => 100,
                'uses_count'        => 34,
                'min_nights'        => 2,
                'is_active'         => 1,
                'starts_at'         => Carbon::now()->subMonths(2),
                'expires_at'        => Carbon::now()->addMonths(4),
            ],
            [
                'code'              => 'REZI5000',
                'name'              => 'Réduction 5 000 XOF',
                'description'       => 'Bon de réduction fixe valable sur toutes les résidences.',
                'discount_type'     => 'fixed',
                'discount_value'    => 5000.00,
                'max_discount'      => null,
                'scope'             => 'global',
                'first_booking_only'=> 0,
                'max_uses'          => 50,
                'uses_count'        => 12,
                'min_nights'        => 3,
                'is_active'         => 1,
                'starts_at'         => Carbon::now()->subMonth(),
                'expires_at'        => Carbon::now()->addMonths(2),
            ],
            [
                'code'              => 'ETE2026',
                'name'              => 'Promo Été 2026',
                'description'       => 'Offre spéciale saison estivale.',
                'discount_type'     => 'percentage',
                'discount_value'    => 15.00,
                'max_discount'      => 20000.00,
                'scope'             => 'global',
                'first_booking_only'=> 0,
                'max_uses'          => 200,
                'uses_count'        => 7,
                'min_nights'        => 4,
                'is_active'         => 1,
                'starts_at'         => Carbon::now()->subDays(5),
                'expires_at'        => Carbon::now()->addMonths(3),
            ],
            // Expiré
            [
                'code'              => 'NOEL2025',
                'name'              => 'Noël 2025',
                'description'       => 'Coupon de Noël 2025 — expiré.',
                'discount_type'     => 'percentage',
                'discount_value'    => 25.00,
                'max_discount'      => 25000.00,
                'scope'             => 'global',
                'first_booking_only'=> 0,
                'max_uses'          => 150,
                'uses_count'        => 148,
                'min_nights'        => 2,
                'is_active'         => 0,
                'starts_at'         => Carbon::create(2025, 12, 1),
                'expires_at'        => Carbon::create(2026, 1, 5),
            ],
            // À venir
            [
                'code'              => 'RAMADAN2026',
                'name'              => 'Offre Ramadan 2026',
                'description'       => 'Réduction spéciale période de Ramadan.',
                'discount_type'     => 'percentage',
                'discount_value'    => 10.00,
                'max_discount'      => 10000.00,
                'scope'             => 'global',
                'first_booking_only'=> 0,
                'max_uses'          => 300,
                'uses_count'        => 0,
                'min_nights'        => 2,
                'is_active'         => 1,
                'starts_at'         => Carbon::now()->addDays(15),
                'expires_at'        => Carbon::now()->addDays(45),
            ],
        ];

        foreach ($coupons as $data) {
            Coupon::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, [
                    'user_id'         => $admin?->id,
                    'residence_id'    => null,
                    'max_uses_per_user' => 1,
                    'allowed_communes'  => null,
                    'allowed_types'     => null,
                    'allowed_user_ids'  => null,
                    'created_at'        => Carbon::now()->subDays(rand(5, 60)),
                    'updated_at'        => Carbon::now()->subDays(rand(0, 5)),
                ])
            );
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PROMO CODES
    // ──────────────────────────────────────────────────────────────────────────
    private function seedPromoCodes($admin, $residences, $owners): void
    {
        $codes = [
            [
                'code'              => 'PROMO10',
                'name'              => 'Code Promo -10%',
                'description'       => 'Code promotionnel standard 10% de réduction.',
                'type'              => 'percentage',
                'value'             => 10.00,
                'max_discount'      => 8000.00,
                'max_uses'          => 500,
                'uses_count'        => 87,
                'min_nights'        => null,
                'first_booking_only'=> 0,
                'is_active'         => 1,
                'valid_from'        => Carbon::now()->subMonths(3)->toDateString(),
                'valid_until'       => Carbon::now()->addMonths(6)->toDateString(),
            ],
            [
                'code'              => 'FLASH30',
                'name'              => 'Flash Sale -30%',
                'description'       => 'Code flash valable 48h seulement.',
                'type'              => 'percentage',
                'value'             => 30.00,
                'max_discount'      => 30000.00,
                'max_uses'          => 50,
                'uses_count'        => 49,
                'min_nights'        => 3,
                'first_booking_only'=> 0,
                'is_active'         => 0,
                'valid_from'        => Carbon::now()->subDays(10)->toDateString(),
                'valid_until'       => Carbon::now()->subDays(8)->toDateString(),
            ],
            [
                'code'              => 'VIP15000',
                'name'              => 'Réduction VIP 15 000 XOF',
                'description'       => 'Code réservé aux clients VIP.',
                'type'              => 'fixed',
                'value'             => 15000.00,
                'max_discount'      => null,
                'max_uses'          => 20,
                'uses_count'        => 3,
                'min_nights'        => 5,
                'first_booking_only'=> 0,
                'is_active'         => 1,
                'valid_from'        => Carbon::now()->subMonth()->toDateString(),
                'valid_until'       => Carbon::now()->addMonths(5)->toDateString(),
            ],
            [
                'code'              => 'STUDENT2026',
                'name'              => 'Tarif Étudiant 2026',
                'description'       => 'Code exclusif pour les étudiants.',
                'type'              => 'percentage',
                'value'             => 20.00,
                'max_discount'      => 10000.00,
                'max_uses'          => 1000,
                'uses_count'        => 156,
                'min_nights'        => null,
                'first_booking_only'=> 0,
                'is_active'         => 1,
                'valid_from'        => Carbon::create(2026, 1, 1)->toDateString(),
                'valid_until'       => Carbon::create(2026, 12, 31)->toDateString(),
            ],
            [
                'code'              => 'ABIDJAN5',
                'name'              => 'Abidjan Week -5%',
                'description'       => 'Offre spéciale résidences Abidjan.',
                'type'              => 'percentage',
                'value'             => 5.00,
                'max_discount'      => 5000.00,
                'max_uses'          => null,
                'uses_count'        => 22,
                'min_nights'        => 2,
                'first_booking_only'=> 0,
                'is_active'         => 1,
                'valid_from'        => Carbon::now()->subDays(15)->toDateString(),
                'valid_until'       => Carbon::now()->addDays(30)->toDateString(),
            ],
        ];

        foreach ($codes as $data) {
            PromoCode::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, [
                    'user_id'           => $admin?->id,
                    'residence_id'      => null,
                    'min_amount'        => null,
                    'max_uses_per_user' => 1,
                    'created_at'        => Carbon::now()->subDays(rand(5, 90)),
                    'updated_at'        => Carbon::now()->subDays(rand(0, 5)),
                ])
            );
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CAMPAIGNS (Campagnes marketing)
    // ──────────────────────────────────────────────────────────────────────────
    private function seedCampaigns($admin): void
    {
        $campaigns = [
            // Envoyées (historique)
            [
                'name'               => 'Newsletter Avril 2026 — Nouvelles résidences',
                'description'        => 'Annonce des nouvelles résidences disponibles ce mois.',
                'type'               => 'email',
                'subject'            => '🏠 Découvrez les nouvelles résidences REZI !',
                'content'            => '<h2>Bonjour,</h2><p>De nouvelles résidences viennent d\'être ajoutées sur REZI. Découvrez-les dès maintenant et profitez de nos offres exclusives.</p>',
                'audience'           => 'all_users',
                'status'             => 'sent',
                'recipients_count'   => 120,
                'delivered_count'    => 117,
                'opened_count'       => 68,
                'clicked_count'      => 24,
                'bounced_count'      => 3,
                'unsubscribed_count' => 1,
                'scheduled_at'       => Carbon::now()->subDays(15)->setHour(9),
                'sent_at'            => Carbon::now()->subDays(15)->setHour(9)->addMinutes(5),
            ],
            [
                'name'               => 'Relance clients inactifs — Mars 2026',
                'description'        => 'Campagne de réactivation des clients n\'ayant pas réservé depuis 60 jours.',
                'type'               => 'email',
                'subject'            => '😢 Vous nous manquez ! -15% sur votre prochaine réservation',
                'content'            => '<h2>Cela fait un moment...</h2><p>Utilisez le code <strong>RETOUR15</strong> pour obtenir 15% de réduction sur votre prochaine réservation.</p>',
                'audience'           => 'inactive_users',
                'status'             => 'sent',
                'recipients_count'   => 45,
                'delivered_count'    => 44,
                'opened_count'       => 21,
                'clicked_count'      => 8,
                'bounced_count'      => 1,
                'unsubscribed_count' => 0,
                'scheduled_at'       => Carbon::now()->subDays(28)->setHour(10),
                'sent_at'            => Carbon::now()->subDays(28)->setHour(10)->addMinutes(3),
            ],
            [
                'name'               => 'Campagne Propriétaires — Mise en avant',
                'description'        => 'Présentation de la fonctionnalité Mise en avant aux propriétaires.',
                'type'               => 'email',
                'subject'            => '🚀 Boostez votre résidence avec la Mise en avant REZI',
                'content'            => '<h2>Propriétaire REZI,</h2><p>Augmentez votre visibilité et vos réservations grâce à notre nouvelle fonctionnalité Mise en avant.</p>',
                'audience'           => 'owners',
                'status'             => 'sent',
                'recipients_count'   => 5,
                'delivered_count'    => 5,
                'opened_count'       => 4,
                'clicked_count'      => 2,
                'bounced_count'      => 0,
                'unsubscribed_count' => 0,
                'scheduled_at'       => Carbon::now()->subDays(45)->setHour(14),
                'sent_at'            => Carbon::now()->subDays(45)->setHour(14)->addMinutes(2),
            ],
            // Planifiée
            [
                'name'               => 'Newsletter Mai 2026 — Offres spéciales',
                'description'        => 'Campagne mensuelle avec les meilleures offres du mois.',
                'type'               => 'email',
                'subject'            => '🌟 Les meilleures offres REZI en mai 2026',
                'content'            => '<h2>Les offres de mai sont là !</h2><p>Découvrez nos promotions exclusives pour le mois de mai.</p>',
                'audience'           => 'all_users',
                'status'             => 'scheduled',
                'recipients_count'   => 0,
                'delivered_count'    => 0,
                'opened_count'       => 0,
                'clicked_count'      => 0,
                'bounced_count'      => 0,
                'unsubscribed_count' => 0,
                'scheduled_at'       => Carbon::create(2026, 5, 1)->setHour(9),
                'sent_at'            => null,
            ],
            // Brouillon
            [
                'name'               => 'Campagne Nouveaux Utilisateurs — Onboarding',
                'description'        => 'Email de bienvenue avec guide d\'utilisation REZI.',
                'type'               => 'email',
                'subject'            => '👋 Bienvenue sur REZI — Comment commencer ?',
                'content'            => '<h2>Bienvenue !</h2><p>Voici comment trouver votre premier logement sur REZI en 3 étapes simples.</p>',
                'audience'           => 'new_users',
                'status'             => 'draft',
                'recipients_count'   => 0,
                'delivered_count'    => 0,
                'opened_count'       => 0,
                'clicked_count'      => 0,
                'bounced_count'      => 0,
                'unsubscribed_count' => 0,
                'scheduled_at'       => null,
                'sent_at'            => null,
            ],
            // In-app notification (envoyée)
            [
                'name'               => 'Notification Offres Flash — Semaine 15',
                'description'        => 'Notification in-app pour les promotions flash de la semaine.',
                'type'               => 'in_app',
                'subject'            => 'Offres flash cette semaine 🔥',
                'content'            => 'Des promotions exclusives sont disponibles cette semaine. Réservez avant qu\'il soit trop tard !',
                'audience'           => 'clients',
                'status'             => 'sent',
                'recipients_count'   => 90,
                'delivered_count'    => 89,
                'opened_count'       => 55,
                'clicked_count'      => 31,
                'bounced_count'      => 1,
                'unsubscribed_count' => 0,
                'scheduled_at'       => Carbon::now()->subDays(7)->setHour(18),
                'sent_at'            => Carbon::now()->subDays(7)->setHour(18)->addMinutes(1),
            ],
        ];

        foreach ($campaigns as $data) {
            Campaign::create(array_merge($data, [
                'user_id'          => $admin?->id,
                'template'         => null,
                'audience_filters' => null,
                'excluded_user_ids'=> null,
                'track_opens'      => 1,
                'track_clicks'     => 1,
                'created_at'       => Carbon::now()->subDays(rand(1, 60)),
                'updated_at'       => Carbon::now()->subDays(rand(0, 3)),
            ]));
        }
    }
}
