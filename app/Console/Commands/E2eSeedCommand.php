<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Residence;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Crée ou réinitialise les comptes de test E2E Playwright.
 *
 * Usage :
 *   php artisan e2e:seed
 *
 * Comptes créés :
 *  - e2e.client@rezi.test  / password  (role: user)
 *  - e2e.owner@rezi.test   / password  (role: owner)
 *  - e2e.admin@rezi.test   / password  (role: admin)
 *
 * NOTE : Ne jamais exécuter en production !
 */
class E2eSeedCommand extends Command
{
    protected $signature = 'e2e:seed';

    protected $description = 'Seed Playwright E2E test accounts (local/testing only)';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('❌ Cette commande ne doit pas être exécutée en production.');

            return self::FAILURE;
        }

        $this->info('🌱 Création des comptes E2E …');

        // Le modèle User a le cast 'password' => 'hashed' (Laravel 10+) qui re-hashe
        // automatiquement toute valeur assignée via Eloquent. On utilise DB::table()
        // pour écrire le hash directement et éviter le double-hashage.
        $passwordHash = Hash::make('password');

        // ─── Client ──────────────────────────────────────────────────────────
        $clientId = DB::table('users')->where('email', 'e2e.client@rezi.test')->value('id');
        if ($clientId) {
            DB::table('users')->where('id', $clientId)->update([
                'name'               => 'E2E Client',
                'password'           => $passwordHash,
                'email_verified_at'  => now(),
                'role'               => 'user',
                'two_factor_enabled' => false,
                'updated_at'         => now(),
            ]);
        } else {
            $clientId = DB::table('users')->insertGetId([
                'name'               => 'E2E Client',
                'email'              => 'e2e.client@rezi.test',
                'password'           => $passwordHash,
                'email_verified_at'  => now(),
                'role'               => 'user',
                'two_factor_enabled' => false,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
        $client = User::find($clientId);

        // ─── Owner ───────────────────────────────────────────────────────────
        $ownerId = DB::table('users')->where('email', 'e2e.owner@rezi.test')->value('id');
        if ($ownerId) {
            DB::table('users')->where('id', $ownerId)->update([
                'name'                 => 'E2E Owner',
                'password'             => $passwordHash,
                'email_verified_at'    => now(),
                'role'                 => 'owner',
                'two_factor_enabled'   => false,
                'identity_verified'    => true,
                'identity_verified_at' => now(),
                'updated_at'           => now(),
            ]);
        } else {
            $ownerId = DB::table('users')->insertGetId([
                'name'                 => 'E2E Owner',
                'email'                => 'e2e.owner@rezi.test',
                'password'             => $passwordHash,
                'email_verified_at'    => now(),
                'role'                 => 'owner',
                'two_factor_enabled'   => false,
                'identity_verified'    => true,
                'identity_verified_at' => now(),
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }
        $owner = User::find($ownerId);

        // Créer au moins une résidence pour l'owner si elle n'existe pas
        if (Residence::where('owner_id', $owner->id)->doesntExist()) {
            Residence::factory()->create([
                'owner_id'      => $owner->id,
                'status'        => 'active',
                'is_available'  => true,
            ]);
            $this->line('  → Résidence de démo créée pour l\'owner E2E');
        }

        // ─── Admin ───────────────────────────────────────────────────────────
        $adminId = DB::table('users')->where('email', 'e2e.admin@rezi.test')->value('id');
        if ($adminId) {
            DB::table('users')->where('id', $adminId)->update([
                'name'               => 'E2E Admin',
                'password'           => $passwordHash,
                'email_verified_at'  => now(),
                'role'               => 'admin',
                'two_factor_enabled' => false,
                'updated_at'         => now(),
            ]);
        } else {
            $adminId = DB::table('users')->insertGetId([
                'name'               => 'E2E Admin',
                'email'              => 'e2e.admin@rezi.test',
                'password'           => $passwordHash,
                'email_verified_at'  => now(),
                'role'               => 'admin',
                'two_factor_enabled' => false,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
        $admin = User::find($adminId);

        $this->info('✅ Comptes E2E prêts :');
        $this->table(
            ['Email', 'Rôle', 'ID'],
            [
                [$client->email, $client->role, $client->id],
                [$owner->email, $owner->role, $owner->id],
                [$admin->email, $admin->role, $admin->id],
            ],
        );

        return self::SUCCESS;
    }
}
