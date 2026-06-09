<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasRoles
{
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isGuest(): bool
    {
        return (bool) $this->is_guest;
    }

    public static function createOrFindGuest(string $email, string $name, ?string $phone = null): static
    {
        $user = static::where('email', $email)->first();

        if ($user) {
            if (! $user->is_guest) {
                return $user;
            }
            $user->update([
                'name' => $name,
                'phone' => $phone ?? $user->phone,
            ]);

            return $user;
        }

        $guest = static::create([
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'password' => bcrypt(Str::random(32)),
        ]);

        $guest->role = 'user';
        $guest->is_guest = true;
        $guest->guest_token = Str::random(64);
        $guest->save();

        return $guest;
    }
}
