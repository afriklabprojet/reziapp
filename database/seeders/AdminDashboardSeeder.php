<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\FraudReport;
use App\Models\NewsletterSubscriber;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentProvider;
use App\Models\Payout;
use App\Models\Residence;
use App\Models\SponsoredListing;
use App\Models\SupportTicket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminDashboardSeeder extends Seeder
{
    /**
     * Seed realistic data for the admin Filament dashboard.
     * Creates: Bookings, Payments, Payouts, SupportTickets,
     *          FraudReports, NewsletterSubscribers, SponsoredListings.
     */
    public function run(): void
    {
        $this->command->info('📊 Seeding admin dashboard data...');

        $users      = User::where('role', 'user')->get();
        $owners     = User::where('role', 'owner')->get();
        $residences = Residence::where('status', 'active')->get();

        if ($users->isEmpty() || $residences->isEmpty()) {
            $this->command->warn('⚠️  Run DatabaseSeeder first (needs users + residences).');

            return;
        }

        // ─── 1. Reservations ────────────────────────────────────────────────────
        $this->command->info('  → Bookings...');
        $this->seedBookings($users, $residences);

        // ─── 2. Payments ────────────────────────────────────────────────────────
        $this->command->info('  → Payments...');
        $this->seedPayments($users);

        // ─── 3. Payouts (versements en attente) ──────────────────────────────────
        $this->command->info('  → Payouts...');
        $this->seedPayouts($owners);

        // ─── 4. Support Tickets ─────────────────────────────────────────────────
        $this->command->info('  → Support tickets...');
        $this->seedSupportTickets($users);

        // ─── 5. Fraud Reports ───────────────────────────────────────────────────
        $this->command->info('  → Fraud reports...');
        $this->seedFraudReports($users);

        // ─── 6. Newsletter Subscribers ──────────────────────────────────────────
        $this->command->info('  → Newsletter subscribers...');
        $this->seedNewsletterSubscribers($users);

        // ─── 7. Sponsored Listings ──────────────────────────────────────────────
        $this->command->info('  → Sponsored listings...');
        $this->seedSponsoredListings($owners, $residences);

        $this->command->info('✅ Admin dashboard data seeded!');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // BOOKINGS
    // ──────────────────────────────────────────────────────────────────────────
    private function seedBookings($users, $residences): void
    {
        $statuses = [
            'completed' => 50,
            'confirmed' => 25,
            'pending'   => 15,
            'cancelled' => 10,
        ];

        foreach ($statuses as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $daysAgo   = rand(1, 180);
                $checkIn   = Carbon::now()->subDays($daysAgo + rand(1, 30));
                $checkOut  = $checkIn->copy()->addDays(rand(2, 14));
                $nights    = $checkIn->diffInDays($checkOut);
                $ppn       = rand(15000, 80000);
                $subtotal  = $ppn * $nights;
                $cleaning  = rand(5000, 15000);
                $service   = rand(2000, 8000);

                Booking::create([
                    'user_id'       => $users->random()->id,
                    'residence_id'  => $residences->random()->id,
                    'reference'     => 'BK-'.strtoupper(Str::random(8)),
                    'check_in'      => $checkIn,
                    'check_out'     => $checkOut,
                    'guests'        => rand(1, 4),
                    'nights'        => $nights,
                    'price_per_night' => $ppn,
                    'subtotal'      => $subtotal,
                    'cleaning_fee'  => $cleaning,
                    'service_fee'   => $service,
                    'total_amount'  => $subtotal + $cleaning + $service,
                    'status'        => $status,
                    'confirmed_at'  => in_array($status, ['confirmed', 'completed']) ? $checkIn->copy()->subDays(2) : null,
                    'cancelled_at'  => $status === 'cancelled' ? $checkIn->copy()->subDays(1) : null,
                    'created_at'    => Carbon::now()->subDays($daysAgo),
                    'updated_at'    => Carbon::now()->subDays(rand(0, $daysAgo)),
                ]);
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PAYMENTS
    // ──────────────────────────────────────────────────────────────────────────
    private function seedPayments($users): void
    {
        $bookings = Booking::all();

        // Paiements complétés — répartis sur 12 mois
        for ($monthOffset = 0; $monthOffset < 12; $monthOffset++) {
            $monthlyCount = rand(8, 20);
            for ($i = 0; $i < $monthlyCount; $i++) {
                $daysIntoMonth = rand(0, 27);
                $date = Carbon::now()
                    ->subMonths($monthOffset)
                    ->startOfMonth()
                    ->addDays($daysIntoMonth);

                $amount = rand(30000, 250000);
                $fee    = round($amount * 0.02);

                Payment::create([
                    'uuid'        => Str::uuid(),
                    'user_id'     => $users->random()->id,
                    'booking_id'  => $bookings->isNotEmpty() ? $bookings->random()->id : null,
                    'reference'   => 'PAY-'.strtoupper(Str::random(8)),
                    'amount'      => $amount,
                    'fee'         => $fee,
                    'total_amount' => $amount + $fee,
                    'currency'    => 'XOF',
                    'type'        => 'booking',
                    'status'      => 'completed',
                    'initiated_at' => $date,
                    'completed_at' => $date->copy()->addMinutes(rand(2, 10)),
                    'expires_at'  => $date->copy()->addHour(),
                    'metadata'    => [],
                    'created_at'  => $date,
                    'updated_at'  => $date->copy()->addMinutes(rand(5, 30)),
                ]);
            }
        }

        // Paiements en attente (ce mois)
        for ($i = 0; $i < 8; $i++) {
            $amount = rand(20000, 150000);
            Payment::create([
                'uuid'        => Str::uuid(),
                'user_id'     => $users->random()->id,
                'booking_id'  => $bookings->isNotEmpty() ? $bookings->random()->id : null,
                'reference'   => 'PAY-'.strtoupper(Str::random(8)),
                'amount'      => $amount,
                'fee'         => round($amount * 0.02),
                'total_amount' => round($amount * 1.02),
                'currency'    => 'XOF',
                'type'        => 'booking',
                'status'      => 'pending',
                'expires_at'  => now()->addHours(rand(1, 24)),
                'metadata'    => [],
                'created_at'  => now()->subHours(rand(1, 72)),
                'updated_at'  => now()->subHours(rand(1, 48)),
            ]);
        }

        // Paiements échoués ce mois
        for ($i = 0; $i < 4; $i++) {
            $amount = rand(20000, 100000);
            Payment::create([
                'uuid'        => Str::uuid(),
                'user_id'     => $users->random()->id,
                'reference'   => 'PAY-'.strtoupper(Str::random(8)),
                'amount'      => $amount,
                'fee'         => round($amount * 0.02),
                'total_amount' => round($amount * 1.02),
                'currency'    => 'XOF',
                'type'        => 'booking',
                'status'      => 'failed',
                'failed_at'   => now()->subHours(rand(1, 48)),
                'expires_at'  => now()->subHours(1),
                'metadata'    => [],
                'created_at'  => now()->subHours(rand(2, 96)),
                'updated_at'  => now()->subHours(rand(1, 48)),
            ]);
        }

        // Remboursements
        for ($i = 0; $i < 3; $i++) {
            $amount = rand(20000, 100000);
            Payment::create([
                'uuid'        => Str::uuid(),
                'user_id'     => $users->random()->id,
                'reference'   => 'PAY-'.strtoupper(Str::random(8)),
                'amount'      => $amount,
                'fee'         => round($amount * 0.02),
                'total_amount' => round($amount * 1.02),
                'currency'    => 'XOF',
                'type'        => 'booking',
                'status'      => 'refunded',
                'completed_at' => now()->subDays(rand(5, 30)),
                'expires_at'  => now()->subDays(rand(5, 30))->addHour(),
                'metadata'    => [],
                'created_at'  => now()->subDays(rand(10, 60)),
                'updated_at'  => now()->subDays(rand(5, 30)),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PAYOUTS
    // ──────────────────────────────────────────────────────────────────────────
    private function seedPayouts($owners): void
    {
        $provider = PaymentProvider::first();

        // Versements en attente
        for ($i = 0; $i < 5; $i++) {
            $gross      = rand(50000, 400000);
            $platformFee = round($gross * 0.10);
            $transferFee = 1000;
            $net        = $gross - $platformFee - $transferFee;

            Payout::create([
                'uuid'                => Str::uuid(),
                'user_id'             => $owners->random()->id,
                'payment_provider_id' => $provider?->id,
                'gross_amount'        => $gross,
                'platform_fee'        => $platformFee,
                'transfer_fee'        => $transferFee,
                'net_amount'          => $net,
                'currency'            => 'XOF',
                'status'              => 'pending',
                'reference'           => 'PO-'.strtoupper(Str::random(8)),
                'payout_method'       => 'mobile_money',
                'phone_number'        => '+225 07 '.rand(10, 99).' '.rand(10, 99).' '.rand(10, 99).' '.rand(10, 99),
                'created_at'          => now()->subDays(rand(1, 14)),
                'updated_at'          => now()->subDays(rand(0, 7)),
            ]);
        }

        // Versements complétés (historique)
        for ($i = 0; $i < 15; $i++) {
            $gross      = rand(50000, 500000);
            $platformFee = round($gross * 0.10);
            $transferFee = 1000;
            $net        = $gross - $platformFee - $transferFee;
            $daysAgo    = rand(15, 180);

            Payout::create([
                'uuid'                => Str::uuid(),
                'user_id'             => $owners->random()->id,
                'payment_provider_id' => $provider?->id,
                'gross_amount'        => $gross,
                'platform_fee'        => $platformFee,
                'transfer_fee'        => $transferFee,
                'net_amount'          => $net,
                'currency'            => 'XOF',
                'status'              => 'completed',
                'reference'           => 'PO-'.strtoupper(Str::random(8)),
                'payout_method'       => 'mobile_money',
                'phone_number'        => '+225 07 '.rand(10, 99).' '.rand(10, 99).' '.rand(10, 99).' '.rand(10, 99),
                'created_at'          => Carbon::now()->subDays($daysAgo),
                'updated_at'          => Carbon::now()->subDays($daysAgo - 1),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SUPPORT TICKETS
    // ──────────────────────────────────────────────────────────────────────────
    private function seedSupportTickets($users): void
    {
        $categories = ['technical', 'billing', 'booking', 'property', 'account', 'other'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        $subjects = [
            'Problème de connexion à mon compte',
            'Paiement débité mais réservation non confirmée',
            'Photo de résidence incorrecte',
            'Annulation de réservation urgente',
            'Problème avec le remboursement',
            'Signalement de comportement inapproprié',
            'Résidence différente des photos',
            'Impossible de contacter le propriétaire',
            'Demande de facture',
            'Bug sur la carte interactive',
        ];

        // Tickets ouverts (urgents)
        for ($i = 0; $i < 6; $i++) {
            SupportTicket::create([
                'user_id'     => $users->random()->id,
                'reference'   => 'TK-'.strtoupper(Str::random(6)),
                'category'    => $categories[array_rand($categories)],
                'subject'     => $subjects[array_rand($subjects)],
                'description' => 'Bonjour, je rencontre un problème que je souhaite résoudre rapidement. Merci de me contacter dès que possible.',
                'priority'    => 'high',
                'status'      => 'open',
                'created_at'  => now()->subHours(rand(1, 48)),
                'updated_at'  => now()->subHours(rand(0, 24)),
            ]);
        }

        // Tickets en cours
        for ($i = 0; $i < 8; $i++) {
            SupportTicket::create([
                'user_id'     => $users->random()->id,
                'reference'   => 'TK-'.strtoupper(Str::random(6)),
                'category'    => $categories[array_rand($categories)],
                'subject'     => $subjects[array_rand($subjects)],
                'description' => 'Description détaillée du problème rencontré avec la plateforme REZI.',
                'priority'    => $priorities[array_rand($priorities)],
                'status'      => 'in_progress',
                'first_response_at' => now()->subHours(rand(2, 24)),
                'created_at'  => now()->subDays(rand(1, 7)),
                'updated_at'  => now()->subHours(rand(1, 12)),
            ]);
        }

        // Tickets résolus (historique)
        for ($i = 0; $i < 20; $i++) {
            $daysAgo = rand(5, 90);
            SupportTicket::create([
                'user_id'     => $users->random()->id,
                'reference'   => 'TK-'.strtoupper(Str::random(6)),
                'category'    => $categories[array_rand($categories)],
                'subject'     => $subjects[array_rand($subjects)],
                'description' => 'Description du problème résolu.',
                'priority'    => $priorities[array_rand($priorities)],
                'status'      => 'resolved',
                'first_response_at' => Carbon::now()->subDays($daysAgo)->addHours(2),
                'resolved_at' => Carbon::now()->subDays($daysAgo - 1),
                'satisfaction_rating' => rand(3, 5),
                'created_at'  => Carbon::now()->subDays($daysAgo),
                'updated_at'  => Carbon::now()->subDays($daysAgo - 1),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FRAUD REPORTS
    // ──────────────────────────────────────────────────────────────────────────
    private function seedFraudReports($users): void
    {
        $fraudTypes = ['scam', 'fake_listing', 'harassment', 'inappropriate_content', 'payment_fraud', 'other'];
        $descriptions = [
            'Cette annonce semble frauduleuse, les photos sont volées depuis un autre site.',
            'Le propriétaire demande un paiement en dehors de la plateforme.',
            'Comportement harcelant et messages non sollicités.',
            'Photos inappropriées présentes dans l\'annonce.',
            'Tentative de paiement frauduleux détectée.',
            'Fausse identité vérifiée, documents suspects.',
        ];

        // Signalements en attente (à traiter)
        for ($i = 0; $i < 4; $i++) {
            $reporter = $users->random();
            $target   = $users->where('id', '!=', $reporter->id)->random();

            FraudReport::create([
                'reporter_id'        => $reporter->id,
                'reporter_ip'        => '192.168.'.rand(1, 255).'.'.rand(1, 255),
                'reporter_user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)',
                'target_type'        => 'user',
                'target_id'          => $target->id,
                'target_user_id'     => $target->id,
                'fraud_type'         => $fraudTypes[array_rand($fraudTypes)],
                'description'        => $descriptions[array_rand($descriptions)],
                'risk_score'         => rand(60, 95),
                'status'             => 'pending',
                'priority'           => 'high',
                'is_auto_detected'   => false,
                'created_at'         => now()->subHours(rand(1, 72)),
                'updated_at'         => now()->subHours(rand(0, 24)),
            ]);
        }

        // Signalements résolus (historique)
        for ($i = 0; $i < 12; $i++) {
            $daysAgo  = rand(5, 120);
            $reporter = $users->random();
            $target   = $users->where('id', '!=', $reporter->id)->random();

            FraudReport::create([
                'reporter_id'        => $reporter->id,
                'reporter_ip'        => '192.168.'.rand(1, 255).'.'.rand(1, 255),
                'reporter_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'target_type'        => 'user',
                'target_id'          => $target->id,
                'target_user_id'     => $target->id,
                'fraud_type'         => $fraudTypes[array_rand($fraudTypes)],
                'description'        => $descriptions[array_rand($descriptions)],
                'risk_score'         => rand(20, 80),
                'status'             => 'resolved',
                'priority'           => 'medium',
                'is_auto_detected'   => rand(0, 1) === 1,
                'resolved_at'        => Carbon::now()->subDays($daysAgo - 1),
                'created_at'         => Carbon::now()->subDays($daysAgo),
                'updated_at'         => Carbon::now()->subDays($daysAgo - 1),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // NEWSLETTER SUBSCRIBERS
    // ──────────────────────────────────────────────────────────────────────────
    private function seedNewsletterSubscribers($users): void
    {
        $sources  = ['website', 'mobile_app', 'referral', 'social_media'];
        $firstNames = ['Kouamé', 'Aminata', 'Sékou', 'Fatou', 'Issa', 'Mariama', 'Yao', 'Aïssatou', 'Kofi', 'Binta', 'Jean-Pierre', 'Marie-Claire', 'Oumar', 'Rokhaya', 'Mamadou'];
        $lastNames  = ['Koffi', 'Diallo', 'Touré', 'Coulibaly', 'Traoré', 'Konaté', 'Bamba', 'Dembélé', 'N\'Goran', 'Koné', 'Gbané', 'Sanogo', 'Ouédraogo', 'Sawadogo', 'Diarra'];
        $domains    = ['gmail.com', 'yahoo.fr', 'outlook.com', 'hotmail.fr', 'orange.ci', 'moov.ci'];

        // Abonnés actifs (spread over 12 months)
        for ($i = 0; $i < 120; $i++) {
            $fn    = $firstNames[array_rand($firstNames)];
            $ln    = $lastNames[array_rand($lastNames)];
            $email = Str::lower(Str::ascii($fn)).'.'.Str::lower(Str::ascii($ln)).rand(1, 999).'@'.$domains[array_rand($domains)];
            $daysAgo = rand(0, 360);

            NewsletterSubscriber::firstOrCreate(
                ['email' => $email],
                [
                    'name'          => $fn.' '.$ln,
                    'user_id'       => rand(0, 1) ? $users->random()->id : null,
                    'status'        => 'active',
                    'token'         => Str::random(64),
                    'source'        => $sources[array_rand($sources)],
                    'ip_address'    => '196.'.rand(0, 255).'.'.rand(0, 255).'.'.rand(0, 255),
                    'subscribed_at' => Carbon::now()->subDays($daysAgo),
                    'verified_at'   => Carbon::now()->subDays($daysAgo)->addMinutes(rand(5, 60)),
                    'created_at'    => Carbon::now()->subDays($daysAgo),
                    'updated_at'    => Carbon::now()->subDays($daysAgo),
                ]
            );
        }

        // Abonnés désinscrits
        for ($i = 0; $i < 15; $i++) {
            $fn    = $firstNames[array_rand($firstNames)];
            $ln    = $lastNames[array_rand($lastNames)];
            $email = Str::lower(Str::ascii($fn)).'.unsub'.rand(1, 9999).'@'.$domains[array_rand($domains)];
            $daysAgo = rand(30, 180);

            NewsletterSubscriber::firstOrCreate(
                ['email' => $email],
                [
                    'name'              => $fn.' '.$ln,
                    'status'            => 'unsubscribed',
                    'token'             => Str::random(64),
                    'source'            => $sources[array_rand($sources)],
                    'ip_address'        => '196.'.rand(0, 255).'.'.rand(0, 255).'.'.rand(0, 255),
                    'subscribed_at'     => Carbon::now()->subDays($daysAgo),
                    'unsubscribed_at'   => Carbon::now()->subDays(rand(5, $daysAgo - 1)),
                    'created_at'        => Carbon::now()->subDays($daysAgo),
                    'updated_at'        => Carbon::now()->subDays(rand(0, 5)),
                ]
            );
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SPONSORED LISTINGS (Mise en avant)
    // ──────────────────────────────────────────────────────────────────────────
    private function seedSponsoredListings($owners, $residences): void
    {
        $types  = ['featured', 'premium', 'spotlight', 'top_search'];

        // Campagnes actives
        for ($i = 0; $i < 8; $i++) {
            $daysAgo   = rand(0, 15);
            $duration  = rand(7, 30);
            $daily     = rand(2000, 10000);
            $total     = $daily * $duration;
            $owner     = $owners->random();
            $residence = $residences->where('owner_id', $owner->id)->isNotEmpty()
                ? $residences->where('owner_id', $owner->id)->random()
                : $residences->random();

            SponsoredListing::create([
                'residence_id'      => $residence->id,
                'user_id'           => $owner->id,
                'type'              => $types[array_rand($types)],
                'starts_at'         => Carbon::now()->subDays($daysAgo),
                'ends_at'           => Carbon::now()->subDays($daysAgo)->addDays($duration),
                'duration_days'     => $duration,
                'position'          => rand(1, 10),
                'daily_budget'      => $daily,
                'total_budget'      => $total,
                'amount_spent'      => round($total * ($daysAgo / $duration) * rand(80, 100) / 100),
                'billing_type'      => 'daily',
                'cost_per_unit'     => $daily,
                'impressions'       => rand(500, 8000),
                'clicks'            => rand(20, 400),
                'contacts_generated' => rand(2, 40),
                'status'            => 'active',
                'is_paid'           => true,
                'payment_reference' => 'SP-'.strtoupper(Str::random(8)),
                'created_at'        => Carbon::now()->subDays($daysAgo + 1),
                'updated_at'        => Carbon::now()->subDays(rand(0, $daysAgo)),
            ]);
        }

        // Campagnes en attente de paiement
        for ($i = 0; $i < 3; $i++) {
            $duration = rand(7, 14);
            $daily    = rand(2000, 8000);
            $owner    = $owners->random();
            $residence = $residences->where('owner_id', $owner->id)->isNotEmpty()
                ? $residences->where('owner_id', $owner->id)->random()
                : $residences->random();

            SponsoredListing::create([
                'residence_id'  => $residence->id,
                'user_id'       => $owner->id,
                'type'          => $types[array_rand($types)],
                'starts_at'     => Carbon::now()->addDays(1),
                'ends_at'       => Carbon::now()->addDays($duration + 1),
                'duration_days' => $duration,
                'daily_budget'  => $daily,
                'total_budget'  => $daily * $duration,
                'amount_spent'  => 0,
                'billing_type'  => 'daily',
                'cost_per_unit' => $daily,
                'impressions'   => 0,
                'clicks'        => 0,
                'contacts_generated' => 0,
                'status'        => 'pending',
                'is_paid'       => false,
                'created_at'    => now()->subHours(rand(2, 48)),
                'updated_at'    => now()->subHours(rand(0, 24)),
            ]);
        }

        // Campagnes terminées (historique 3 mois)
        for ($i = 0; $i < 12; $i++) {
            $daysAgo  = rand(30, 120);
            $duration = rand(7, 30);
            $daily    = rand(2000, 10000);
            $total    = $daily * $duration;
            $owner    = $owners->random();
            $residence = $residences->random();

            SponsoredListing::create([
                'residence_id'      => $residence->id,
                'user_id'           => $owner->id,
                'type'              => $types[array_rand($types)],
                'starts_at'         => Carbon::now()->subDays($daysAgo + $duration),
                'ends_at'           => Carbon::now()->subDays($daysAgo),
                'duration_days'     => $duration,
                'daily_budget'      => $daily,
                'total_budget'      => $total,
                'amount_spent'      => $total,
                'billing_type'      => 'daily',
                'cost_per_unit'     => $daily,
                'impressions'       => rand(1000, 15000),
                'clicks'            => rand(50, 800),
                'contacts_generated' => rand(5, 80),
                'status'            => 'completed',
                'is_paid'           => true,
                'payment_reference' => 'SP-'.strtoupper(Str::random(8)),
                'created_at'        => Carbon::now()->subDays($daysAgo + $duration + 1),
                'updated_at'        => Carbon::now()->subDays($daysAgo),
            ]);
        }
    }
}
