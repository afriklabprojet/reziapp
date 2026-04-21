<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SharedDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'residence_id',
        'conversation_id',
        'name',
        'type',
        'file_path',
        'file_size',
        'access_type',
        'expires_at',
        'download_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'download_count' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Types de documents
     */
    public const TYPE_RULES = 'rules';
    public const TYPE_FLOOR_PLAN = 'floor_plan';
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_GUIDE = 'guide';
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_OTHER = 'other';

    /**
     * Types d'accès
     */
    public const ACCESS_PUBLIC = 'public';
    public const ACCESS_CONVERSATION = 'conversation';
    public const ACCESS_PRIVATE = 'private';

    /**
     * Propriétaire du document
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Résidence associée
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Conversation associée
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Obtenir l'URL de téléchargement
     */
    public function getDownloadUrl(): string
    {
        return route('documents.download', $this);
    }

    /**
     * Obtenir l'URL temporaire signée
     */
    public function getSignedUrl(int $minutes = 60): string
    {
        return Storage::temporaryUrl($this->file_path, now()->addMinutes($minutes));
    }

    /**
     * Incrémenter le compteur de téléchargements
     */
    public function incrementDownloads(): void
    {
        $this->increment('download_count');
    }

    /**
     * Vérifier si le document est expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Vérifier si un utilisateur peut accéder au document
     */
    public function canAccess(User $user): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        if ($this->access_type === self::ACCESS_PUBLIC) {
            return true;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        if ($this->access_type === self::ACCESS_CONVERSATION && $this->conversation) {
            return $this->conversation->user_id === $user->id
                || $this->conversation->owner_id === $user->id;
        }

        return false;
    }

    /**
     * Formater la taille du fichier
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    /**
     * Obtenir l'icône selon le type
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_RULES => 'document-text',
            self::TYPE_FLOOR_PLAN => 'map',
            self::TYPE_CONTRACT => 'document-check',
            self::TYPE_GUIDE => 'book-open',
            self::TYPE_INVOICE => 'receipt',
            default => 'document',
        };
    }

    /**
     * Supprimer le fichier physique
     */
    public function deleteFile(): bool
    {
        return Storage::delete($this->file_path);
    }

    /**
     * Scope par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour une résidence
     */
    public function scopeForResidence($query, int $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }

    /**
     * Scope pour une conversation
     */
    public function scopeForConversation($query, int $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * Scope non expirés
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Liste des types disponibles
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_RULES => 'Règlement intérieur',
            self::TYPE_FLOOR_PLAN => 'Plan du logement',
            self::TYPE_CONTRACT => 'Contrat',
            self::TYPE_GUIDE => 'Guide d\'accueil',
            self::TYPE_INVOICE => 'Facture',
            self::TYPE_OTHER => 'Autre',
        ];
    }
}
