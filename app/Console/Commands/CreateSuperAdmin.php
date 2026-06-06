<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    protected $signature = 'rezi:create-super-admin
                            {--name= : Nom complet}
                            {--email= : Adresse email}
                            {--password= : Mot de passe (min 12 caractères)}';

    protected $description = 'Créer un compte super administrateur ReziApp';

    public function handle(): int
    {
        $this->info('=== Création Super Admin ReziApp ===');

        $name     = $this->option('name')     ?? $this->ask('Nom complet');
        $email    = $this->option('email')    ?? $this->ask('Adresse email');
        $password = $this->option('password') ?? $this->secret('Mot de passe (min 12 caractères)');

        // Validation
        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:12'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name'              => $name,
            'email'             => $email,
            'password'          => Hash::make($password),
            'email_verified_at' => now(),
            'email_verified'    => true,
        ]);

        // Assign role hors fillable pour sécurité
        $user->role = 'super_admin';
        $user->save();

        $this->info('');
        $this->info('✅ Super admin créé avec succès :');
        $this->table(
            ['ID', 'Nom', 'Email', 'Rôle'],
            [[$user->id, $user->name, $user->email, $user->role]]
        );
        $this->warn('⚠️  Conservez ces identifiants en lieu sûr et changez le mot de passe après la première connexion.');

        return self::SUCCESS;
    }
}
