<?php

namespace Database\Seeders;

use App\Models\IdentityVerification;
use App\Models\PhoneVerification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IdentityVerificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('id', [2, 3, 4, 5, 6, 7, 8, 9, 10])->get()->keyBy('id');

        // ── VÉRIFICATIONS D'IDENTITÉ ──────────────────────────────────────
        $verifications = [
            // Approuvée — owner 2
            [
                'user_id'         => 2,
                'document_type'   => 'cni',
                'document_number' => encrypt('CI-2021-0041872'),
                'document_country' => 'CI',
                'first_name'      => 'Isaias',
                'last_name'       => 'Dicki',
                'birth_date'      => '1985-06-15',
                'document_expiry' => '2028-06-14',
                'status'          => 'approved',
                'face_match_score' => 94.50,
                'face_match_passed' => true,
                'reviewed_by'     => 1,
                'reviewed_at'     => Carbon::now()->subDays(10),
                'admin_notes'     => 'Document clair, correspondance visage excellente.',
                'expires_at'      => Carbon::now()->addYears(2),
                'attempt_count'   => 1,
                'last_attempt_at' => Carbon::now()->subDays(11),
                'created_at'      => Carbon::now()->subDays(11),
                'updated_at'      => Carbon::now()->subDays(10),
            ],
            // Approuvée — owner 3
            [
                'user_id'         => 3,
                'document_type'   => 'passport',
                'document_number' => encrypt('A12345678'),
                'document_country' => 'CI',
                'first_name'      => 'Katrina',
                'last_name'       => 'Windler',
                'birth_date'      => '1990-03-22',
                'document_expiry' => '2030-03-21',
                'status'          => 'approved',
                'face_match_score' => 88.20,
                'face_match_passed' => true,
                'reviewed_by'     => 1,
                'reviewed_at'     => Carbon::now()->subDays(5),
                'admin_notes'     => 'Passeport valide.',
                'expires_at'      => Carbon::now()->addYears(2),
                'attempt_count'   => 1,
                'last_attempt_at' => Carbon::now()->subDays(6),
                'created_at'      => Carbon::now()->subDays(6),
                'updated_at'      => Carbon::now()->subDays(5),
            ],
            // Revue manuelle — owner 4
            [
                'user_id'         => 4,
                'document_type'   => 'cni',
                'document_number' => encrypt('CI-2019-0078234'),
                'document_country' => 'CI',
                'first_name'      => 'Caitlyn',
                'last_name'       => 'Mayer',
                'birth_date'      => '1993-11-08',
                'document_expiry' => '2027-11-07',
                'status'          => 'manual_review',
                'face_match_score' => 71.00,
                'face_match_passed' => false,
                'reviewed_by'     => null,
                'reviewed_at'     => null,
                'admin_notes'     => null,
                'expires_at'      => null,
                'attempt_count'   => 1,
                'last_attempt_at' => Carbon::now()->subHours(3),
                'created_at'      => Carbon::now()->subHours(3),
                'updated_at'      => Carbon::now()->subHours(3),
            ],
            // Soumis (en attente) — owner 5
            [
                'user_id'         => 5,
                'document_type'   => 'cni',
                'document_number' => encrypt('CI-2022-0056123'),
                'document_country' => 'CI',
                'first_name'      => 'Cristal',
                'last_name'       => 'Erdman',
                'birth_date'      => '1988-07-04',
                'document_expiry' => '2029-07-03',
                'status'          => 'submitted',
                'face_match_score' => null,
                'face_match_passed' => false,
                'reviewed_by'     => null,
                'reviewed_at'     => null,
                'admin_notes'     => null,
                'expires_at'      => null,
                'attempt_count'   => 1,
                'last_attempt_at' => Carbon::now()->subHours(1),
                'created_at'      => Carbon::now()->subHours(1),
                'updated_at'      => Carbon::now()->subHours(1),
            ],
            // Rejetée — user 7 (2ème tentative)
            [
                'user_id'         => 7,
                'document_type'   => 'cni',
                'document_number' => encrypt('CI-2017-0099821'),
                'document_country' => 'CI',
                'first_name'      => 'Domenick',
                'last_name'       => 'Schmeler',
                'birth_date'      => '1982-02-14',
                'document_expiry' => '2025-02-13', // document expiré
                'status'          => 'rejected',
                'face_match_score' => 45.00,
                'face_match_passed' => false,
                'rejection_reason' => 'Document expiré. Veuillez soumettre un document valide.',
                'reviewed_by'     => 1,
                'reviewed_at'     => Carbon::now()->subDays(2),
                'admin_notes'     => 'Document CNI expiré depuis 2025-02-13.',
                'expires_at'      => null,
                'attempt_count'   => 2,
                'last_attempt_at' => Carbon::now()->subDays(2),
                'created_at'      => Carbon::now()->subDays(3),
                'updated_at'      => Carbon::now()->subDays(2),
            ],
            // En attente — user 8
            [
                'user_id'         => 8,
                'document_type'   => 'passport',
                'document_number' => encrypt('B98765432'),
                'document_country' => 'CI',
                'first_name'      => 'Kaylie',
                'last_name'       => 'Bayer',
                'birth_date'      => '1995-09-28',
                'document_expiry' => '2031-09-27',
                'status'          => 'manual_review',
                'face_match_score' => 82.50,
                'face_match_passed' => true,
                'reviewed_by'     => null,
                'reviewed_at'     => null,
                'admin_notes'     => null,
                'expires_at'      => null,
                'attempt_count'   => 1,
                'last_attempt_at' => Carbon::now()->subMinutes(30),
                'created_at'      => Carbon::now()->subMinutes(30),
                'updated_at'      => Carbon::now()->subMinutes(30),
            ],
        ];

        foreach ($verifications as $data) {
            $rejection = $data['rejection_reason'] ?? null;
            unset($data['rejection_reason']);

            $v = IdentityVerification::create($data);

            if ($rejection) {
                $v->update(['rejection_reason' => $rejection]);
            }

            // Marquer user comme vérifié si approuvé
            if ($data['status'] === 'approved') {
                User::where('id', $data['user_id'])->update([
                    'identity_verified' => true,
                ]);
            }
        }

        $this->command->info('✅ identity_verifications seeded: '.IdentityVerification::count().' enregistrements.');
        $this->command->info('   → approved: '.IdentityVerification::where('status', 'approved')->count());
        $this->command->info('   → manual_review: '.IdentityVerification::where('status', 'manual_review')->count());
        $this->command->info('   → submitted: '.IdentityVerification::where('status', 'submitted')->count());
        $this->command->info('   → rejected: '.IdentityVerification::where('status', 'rejected')->count());

        // ── VÉRIFICATIONS TÉLÉPHONE ─────────────────────────────────────
        $phoneVerifs = [
            ['user_id' => 2, 'phone' => '+2250701234567', 'status' => 'verified', 'verified_at' => Carbon::now()->subDays(9)],
            ['user_id' => 3, 'phone' => '+2250702345678', 'status' => 'verified', 'verified_at' => Carbon::now()->subDays(4)],
            ['user_id' => 5, 'phone' => '+2250703456789', 'status' => 'sent',     'verified_at' => null],
            ['user_id' => 9, 'phone' => '+2250704567890', 'status' => 'sent',     'verified_at' => null],
        ];

        foreach ($phoneVerifs as $p) {
            PhoneVerification::create([
                'user_id'        => $p['user_id'],
                'phone'          => $p['phone'],
                'otp_code'       => null, // déjà vérifié ou expiré
                'otp_expires_at' => Carbon::now()->subHours(2),
                'status'         => $p['status'],
                'attempts'       => 0,
                'verified_at'    => $p['verified_at'],
                'last_sent_at'   => Carbon::now()->subHours(2),
                'created_at'     => Carbon::now()->subHours(2),
                'updated_at'     => Carbon::now()->subHours(2),
            ]);

            if ($p['status'] === 'verified') {
                User::where('id', $p['user_id'])->update(['phone_verified' => true]);
            }
        }

        $this->command->info('✅ phone_verifications seeded: '.PhoneVerification::count().' enregistrements.');
    }
}
