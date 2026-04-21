<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CoHost extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'owner_id',
        'user_id',
        'email',
        'name',
        'phone',
        'can_edit_listing',
        'can_manage_calendar',
        'can_manage_pricing',
        'can_respond_messages',
        'can_accept_bookings',
        'can_view_earnings',
        'commission_percent',
        'status',
        'invitation_token',
        'invited_at',
        'accepted_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'can_edit_listing' => 'boolean',
        'can_manage_calendar' => 'boolean',
        'can_manage_pricing' => 'boolean',
        'can_respond_messages' => 'boolean',
        'can_accept_bookings' => 'boolean',
        'can_view_earnings' => 'boolean',
        'commission_percent' => 'decimal:2',
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($coHost) {
            if (!$coHost->invitation_token) {
                $coHost->invitation_token = Str::random(64);
            }
            if (!$coHost->invited_at) {
                $coHost->invited_at = now();
            }
            if (!$coHost->expires_at) {
                $coHost->expires_at = now()->addDays(7);
            }
        });
    }

    /**
     * Relation avec la résidence
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Relation avec le propriétaire principal
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Relation avec l'utilisateur co-hôte
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Activités du co-hôte
     */
    public function activities(): HasMany
    {
        return $this->hasMany(CoHostActivity::class);
    }

    /**
     * Scope pour les invitations en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour les co-hôtes acceptés
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope pour les invitations non expirées
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Vérifie si l'invitation est expirée
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    /**
     * Vérifie si le co-hôte est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Accepter l'invitation
     */
    public function accept(User $user): bool
    {
        if ($this->isExpired() || $this->status !== 'pending') {
            return false;
        }

        $this->update([
            'user_id' => $user->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return true;
    }

    /**
     * Refuser l'invitation
     */
    public function decline(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update(['status' => 'declined']);

        return true;
    }

    /**
     * Révoquer l'accès
     */
    public function revoke(): bool
    {
        $this->update(['status' => 'revoked']);

        return true;
    }

    /**
     * Vérifie si le co-hôte a une permission
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        return match ($permission) {
            'edit_listing' => $this->can_edit_listing,
            'manage_calendar' => $this->can_manage_calendar,
            'manage_pricing' => $this->can_manage_pricing,
            'respond_messages' => $this->can_respond_messages,
            'accept_bookings' => $this->can_accept_bookings,
            'view_earnings' => $this->can_view_earnings,
            default => false,
        };
    }

    /**
     * Obtenir toutes les permissions
     */
    public function getPermissions(): array
    {
        return [
            'edit_listing' => $this->can_edit_listing,
            'manage_calendar' => $this->can_manage_calendar,
            'manage_pricing' => $this->can_manage_pricing,
            'respond_messages' => $this->can_respond_messages,
            'accept_bookings' => $this->can_accept_bookings,
            'view_earnings' => $this->can_view_earnings,
        ];
    }

    /**
     * Logger une activité
     */
    public function logActivity(string $action, ?string $description = null, ?array $metadata = null): CoHostActivity
    {
        return $this->activities()->create([
            'residence_id' => $this->residence_id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
