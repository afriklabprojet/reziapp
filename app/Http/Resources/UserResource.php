<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour User — ne retourner que les données publiques.
 * Les champs sensibles (email, phone) ne sont visibles que par le user lui-même ou un admin.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOwnerOrAdmin = $request->user() && (
            $request->user()->id === $this->id ||
            $request->user()->role === 'admin'
        );

        return [
            'id' => $this->id,
            'name' => $this->name,
            'profile_photo' => $this->profile_photo
                ? url('storage/' . $this->profile_photo)
                : null,
            'role' => $this->role,

            // Champs privés (seulement pour soi-même ou admin)
            'email' => $this->when($isOwnerOrAdmin, $this->email),
            'phone' => $this->when($isOwnerOrAdmin, $this->phone),
            'email_verified_at' => $this->when($isOwnerOrAdmin, $this->email_verified_at?->toIso8601String()),
            'identity_verified' => $this->when($isOwnerOrAdmin, (bool) $this->identity_verified),

            // Dates
            'member_since' => $this->created_at->toIso8601String(),
        ];
    }
}
