<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'content',
        'category',
        'shortcut',
        'variables',
        'language',
        'usage_count',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Catégories de templates
     */
    public const CATEGORY_GREETING = 'greeting';
    public const CATEGORY_AVAILABILITY = 'availability';
    public const CATEGORY_PRICING = 'pricing';
    public const CATEGORY_RULES = 'rules';
    public const CATEGORY_DIRECTIONS = 'directions';
    public const CATEGORY_THANK_YOU = 'thank_you';
    public const CATEGORY_CUSTOM = 'custom';

    /**
     * Propriétaire du template (null si système)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Messages utilisant ce template
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'template_id');
    }

    /**
     * Générer le contenu du message avec les variables
     */
    public function generateContent(array $data = []): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }

        // Nettoyer les variables non remplacées
        $content = preg_replace('/\{[a-zA-Z_]+\}/', '', $content);

        return trim($content);
    }

    /**
     * Extraire les variables du template
     */
    public function extractVariables(): array
    {
        preg_match_all('/\{([a-zA-Z_]+)\}/', $this->content, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Incrémenter le compteur d'utilisation
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope pour les templates système
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope pour les templates utilisateur
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('is_system', true);
        })->where('is_active', true);
    }

    /**
     * Scope par catégorie
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope par langue
     */
    public function scopeLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Scope actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope par raccourci
     */
    public function scopeByShortcut($query, string $shortcut)
    {
        return $query->where('shortcut', $shortcut);
    }

    /**
     * Liste des catégories disponibles
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_GREETING => 'Salutation',
            self::CATEGORY_AVAILABILITY => 'Disponibilité',
            self::CATEGORY_PRICING => 'Tarification',
            self::CATEGORY_RULES => 'Règlement',
            self::CATEGORY_DIRECTIONS => 'Itinéraire',
            self::CATEGORY_THANK_YOU => 'Remerciement',
            self::CATEGORY_CUSTOM => 'Personnalisé',
        ];
    }
}
