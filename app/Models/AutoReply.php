<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'residence_id',
        'name',
        'trigger_type',
        'trigger_conditions',
        'message',
        'is_active',
        'delay_minutes',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur (propriétaire)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec la résidence (optionnelle)
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Scope pour les réponses actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les réponses globales (pas liées à une résidence)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('residence_id');
    }

    /**
     * Scope par type de déclencheur
     */
    public function scopeByTrigger($query, string $type)
    {
        return $query->where('trigger_type', $type);
    }

    /**
     * Vérifie si la réponse doit être déclenchée
     */
    public function shouldTrigger(string $messageContent, array $context = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        switch ($this->trigger_type) {
            case 'first_contact':
                return $context['is_first_message'] ?? false;

            case 'keywords':
                $keywords = $this->trigger_conditions['keywords'] ?? [];
                $lowerContent = mb_strtolower($messageContent);
                foreach ($keywords as $keyword) {
                    if (mb_stripos($lowerContent, mb_strtolower($keyword)) !== false) {
                        return true;
                    }
                }

                return false;

            case 'schedule':
                $schedule = $this->trigger_conditions ?? [];
                $now = now();
                $startTime = $schedule['start_time'] ?? '00:00';
                $endTime = $schedule['end_time'] ?? '23:59';
                $days = array_map('intval', $schedule['days'] ?? [0, 1, 2, 3, 4, 5, 6]);
                $currentTime = $now->format('H:i');

                if (!in_array($now->dayOfWeek, $days)) {
                    return false;
                }

                // Gérer les plages qui traversent minuit (ex : 22:00 → 08:00)
                if ($startTime <= $endTime) {
                    return $currentTime >= $startTime && $currentTime <= $endTime;
                } else {
                    return $currentTime >= $startTime || $currentTime <= $endTime;
                }

                // no break
            case 'manual':
                return false; // Déclenché manuellement uniquement

            default:
                return false;
        }
    }

    /**
     * Remplace les variables dans le message
     */
    public function formatMessage(array $variables = []): string
    {
        $message = $this->message;

        $replacements = [
            '{guest_name}' => $variables['guest_name'] ?? 'Cher client',
            '{residence_name}' => $variables['residence_name'] ?? 'notre résidence',
            '{owner_name}' => $variables['owner_name'] ?? 'Le propriétaire',
            '{price}' => $variables['price'] ?? '',
            '{checkin_time}' => $variables['checkin_time'] ?? '14h00',
            '{checkout_time}' => $variables['checkout_time'] ?? '11h00',
            '{address}' => $variables['address'] ?? '',
            '{phone}' => $variables['phone'] ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    /**
     * Incrémente le compteur d'utilisation
     */
    public function markAsUsed(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Types de déclencheurs disponibles
     */
    public static function getTriggerTypes(): array
    {
        return [
            'first_contact' => 'Premier contact',
            'keywords' => 'Mots-clés détectés',
            'schedule' => 'Horaire programmé',
            'manual' => 'Manuel (bouton rapide)',
        ];
    }
}
