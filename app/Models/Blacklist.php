<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Blacklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'value',
        'value_hash',
        'user_id',
        'reason',
        'description',
        'evidence',
        'is_permanent',
        'expires_at',
        'restriction_level',
        'is_active',
        'created_by',
        'updated_by',
        'appeal_allowed',
        'appeal_message',
        'appeal_submitted_at',
        'appeal_status',
        'appeal_response',
        'appeal_reviewed_by',
    ];

    protected $casts = [
        'evidence' => 'array',
        'is_permanent' => 'boolean',
        'is_active' => 'boolean',
        'appeal_allowed' => 'boolean',
        'expires_at' => 'datetime',
        'appeal_submitted_at' => 'datetime',
    ];

    protected $hidden = [
        'value', // Cacher la vraie valeur
    ];

    // ==========================================
    // BOOT
    // ==========================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Créer un hash pour recherche rapide
            $model->value_hash = hash('sha256', strtolower($model->value));
        });

        static::updating(function ($model) {
            if ($model->isDirty('value')) {
                $model->value_hash = hash('sha256', strtolower($model->value));
            }
        });
    }

    // ==========================================
    // RELATIONS
    // ==========================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function appealReviewer()
    {
        return $this->belongsTo(User::class, 'appeal_reviewed_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBanned($query)
    {
        return $query->where('restriction_level', 'banned');
    }

    // ==========================================
    // MÉTHODES STATIQUES
    // ==========================================

    /**
     * Vérifier si une valeur est blacklistée
     */
    public static function isBlacklisted(string $type, string $value): bool
    {
        $hash = hash('sha256', strtolower($value));

        return self::active()
            ->where('type', $type)
            ->where('value_hash', $hash)
            ->exists();
    }

    /**
     * Obtenir l'entrée blacklist pour une valeur
     */
    public static function getBlacklistEntry(string $type, string $value): ?self
    {
        $hash = hash('sha256', strtolower($value));

        return self::active()
            ->where('type', $type)
            ->where('value_hash', $hash)
            ->first();
    }

    /**
     * Vérifier si un utilisateur est blacklisté
     */
    public static function isUserBlacklisted(User $user): bool
    {
        // Vérifier par user_id
        if (self::active()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Vérifier par email
        if (self::isBlacklisted('email', $user->email)) {
            return true;
        }

        // Vérifier par téléphone
        if ($user->phone && self::isBlacklisted('phone', $user->phone)) {
            return true;
        }

        return false;
    }

    /**
     * Blacklister un utilisateur
     */
    public static function blacklistUser(
        User $user,
        string $reason,
        string $restrictionLevel = 'banned',
        ?string $description = null,
        ?int $createdBy = null,
        bool $permanent = true,
        ?\DateTime $expiresAt = null,
    ): self {
        return self::create([
            'type' => 'user',
            'value' => $user->email,
            'user_id' => $user->id,
            'reason' => $reason,
            'description' => $description,
            'restriction_level' => $restrictionLevel,
            'is_permanent' => $permanent,
            'expires_at' => $expiresAt,
            'created_by' => $createdBy,
        ]);
    }

    // ==========================================
    // MÉTHODES INSTANCE
    // ==========================================

    /**
     * Vérifier si le blacklist est actif
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Désactiver
     */
    public function deactivate(?int $updatedBy = null): void
    {
        $this->update([
            'is_active' => false,
            'updated_by' => $updatedBy,
        ]);
    }

    /**
     * Soumettre un appel
     */
    public function submitAppeal(string $message): bool
    {
        if (!$this->appeal_allowed) {
            return false;
        }

        if ($this->appeal_status !== 'none') {
            return false;
        }

        $this->update([
            'appeal_message' => $message,
            'appeal_status' => 'pending',
            'appeal_submitted_at' => now(),
        ]);

        return true;
    }

    /**
     * Approuver l'appel
     */
    public function approveAppeal(int $reviewerId, ?string $response = null): void
    {
        $this->update([
            'appeal_status' => 'approved',
            'appeal_response' => $response,
            'appeal_reviewed_by' => $reviewerId,
            'is_active' => false,
        ]);
    }

    /**
     * Rejeter l'appel
     */
    public function rejectAppeal(int $reviewerId, string $response): void
    {
        $this->update([
            'appeal_status' => 'rejected',
            'appeal_response' => $response,
            'appeal_reviewed_by' => $reviewerId,
            'appeal_allowed' => false, // Plus d'appel possible
        ]);
    }

    /**
     * Obtenir le label de raison
     */
    public function getReasonLabel(): string
    {
        return match($this->reason) {
            'fraud' => 'Fraude',
            'scam' => 'Arnaque',
            'harassment' => 'Harcèlement',
            'spam' => 'Spam',
            'fake_identity' => 'Fausse identité',
            'payment_default' => 'Défaut de paiement',
            'terms_violation' => 'Violation CGU',
            'legal_request' => 'Demande légale',
            'other' => 'Autre',
            default => $this->reason,
        };
    }

    /**
     * Obtenir le label du niveau de restriction
     */
    public function getRestrictionLabel(): string
    {
        return match($this->restriction_level) {
            'warning' => 'Avertissement',
            'limited' => 'Fonctionnalités limitées',
            'suspended' => 'Suspendu',
            'banned' => 'Banni',
            default => $this->restriction_level,
        };
    }

    /**
     * Obtenir la couleur du niveau
     */
    public function getRestrictionColor(): string
    {
        return match($this->restriction_level) {
            'warning' => 'yellow',
            'limited' => 'orange',
            'suspended' => 'red',
            'banned' => 'gray',
            default => 'gray',
        };
    }
}
