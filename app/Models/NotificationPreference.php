<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'messages_email',
        'messages_push',
        'messages_sms',
        'visits_email',
        'visits_push',
        'visits_sms',
        'payments_email',
        'payments_push',
        'payments_sms',
        'marketing_email',
        'marketing_push',
        'marketing_sms',
        'security_email',
        'security_push',
        'security_sms',
        'quiet_hours_start',
        'quiet_hours_end',
        'timezone',
    ];

    protected $casts = [
        'messages_email' => 'boolean',
        'messages_push' => 'boolean',
        'messages_sms' => 'boolean',
        'visits_email' => 'boolean',
        'visits_push' => 'boolean',
        'visits_sms' => 'boolean',
        'payments_email' => 'boolean',
        'payments_push' => 'boolean',
        'payments_sms' => 'boolean',
        'marketing_email' => 'boolean',
        'marketing_push' => 'boolean',
        'marketing_sms' => 'boolean',
        'security_email' => 'boolean',
        'security_push' => 'boolean',
        'security_sms' => 'boolean',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end' => 'datetime:H:i',
    ];

    /**
     * Catégories de notifications
     */
    public const CATEGORY_MESSAGES = 'messages';
    public const CATEGORY_VISITS = 'visits';
    public const CATEGORY_PAYMENTS = 'payments';
    public const CATEGORY_MARKETING = 'marketing';
    public const CATEGORY_SECURITY = 'security';

    /**
     * Canaux de notification
     */
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_PUSH = 'push';
    public const CHANNEL_SMS = 'sms';

    /**
     * L'utilisateur associé
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifier si un canal est activé pour une catégorie
     */
    public function isEnabled(string $category, string $channel): bool
    {
        $field = "{$category}_{$channel}";

        return $this->{$field} ?? false;
    }

    /**
     * Activer/désactiver un canal pour une catégorie
     */
    public function toggle(string $category, string $channel, bool $enabled): void
    {
        $field = "{$category}_{$channel}";
        $this->update([$field => $enabled]);
    }

    /**
     * Vérifier si on est dans les heures silencieuses
     */
    public function isQuietHours(): bool
    {
        if (!$this->quiet_hours_start || !$this->quiet_hours_end) {
            return false;
        }

        $now = now()->timezone($this->timezone ?? 'Africa/Abidjan');
        $start = $now->copy()->setTimeFromTimeString($this->quiet_hours_start->format('H:i'));
        $end = $now->copy()->setTimeFromTimeString($this->quiet_hours_end->format('H:i'));

        // Si l'heure de fin est avant l'heure de début (ex: 22h - 7h)
        if ($end->lt($start)) {
            return $now->gte($start) || $now->lt($end);
        }

        return $now->between($start, $end);
    }

    /**
     * Obtenir les canaux activés pour une catégorie
     */
    public function getEnabledChannels(string $category): array
    {
        $channels = [];

        foreach ([self::CHANNEL_EMAIL, self::CHANNEL_PUSH, self::CHANNEL_SMS] as $channel) {
            if ($this->isEnabled($category, $channel)) {
                $channels[] = $channel;
            }
        }

        return $channels;
    }

    /**
     * Obtenir les préférences par défaut
     */
    public static function getDefaults(): array
    {
        return [
            'messages_email' => true,
            'messages_push' => true,
            'messages_sms' => false,
            'visits_email' => true,
            'visits_push' => true,
            'visits_sms' => false,
            'payments_email' => true,
            'payments_push' => true,
            'payments_sms' => true,
            'marketing_email' => false,
            'marketing_push' => false,
            'marketing_sms' => false,
            'security_email' => true,
            'security_push' => true,
            'security_sms' => true,
            'timezone' => 'Africa/Abidjan',
        ];
    }

    /**
     * Créer les préférences par défaut pour un utilisateur
     */
    public static function createDefaultsForUser(User $user): self
    {
        return self::create(array_merge(
            ['user_id' => $user->id],
            self::getDefaults(),
        ));
    }

    /**
     * Liste des catégories disponibles
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_MESSAGES => 'Messages',
            self::CATEGORY_VISITS => 'Visites',
            self::CATEGORY_PAYMENTS => 'Paiements',
            self::CATEGORY_MARKETING => 'Marketing',
            self::CATEGORY_SECURITY => 'Sécurité',
        ];
    }

    /**
     * Liste des canaux disponibles
     */
    public static function getChannels(): array
    {
        return [
            self::CHANNEL_EMAIL => 'Email',
            self::CHANNEL_PUSH => 'Notification Push',
            self::CHANNEL_SMS => 'SMS',
        ];
    }
}
