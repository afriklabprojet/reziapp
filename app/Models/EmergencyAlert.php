<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'address',
        'alert_type',
        'message',
        'context',
        'status',
        'notifications_sent',
        'contacts_notified_at',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'context' => 'array',
        'notifications_sent' => 'array',
        'contacts_notified_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['triggered', 'notified', 'acknowledged']);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // ==========================================
    // MÉTHODES
    // ==========================================

    /**
     * Déclencher une alerte
     */
    public static function trigger(
        User $user,
        string $type = 'panic',
        ?string $message = null,
        ?float $lat = null,
        ?float $lng = null,
        ?array $context = null,
    ): self {
        $alert = self::create([
            'user_id' => $user->id,
            'alert_type' => $type,
            'message' => $message,
            'latitude' => $lat,
            'longitude' => $lng,
            'context' => $context,
            'status' => 'triggered',
        ]);

        // Activer le mode urgence sur l'utilisateur
        $user->update(['emergency_mode' => true]);

        return $alert;
    }

    /**
     * Notifier les contacts d'urgence
     */
    public function notifyContacts(): void
    {
        $contacts = $this->user->emergencyContacts()
            ->notifiable()
            ->get();

        $notifications = [];

        foreach ($contacts as $contact) {
            // Envoyer SMS via SmsService
            $sent = \App\Services\SmsService::send(
                $contact->phone,
                $this->getAlertMessage($contact),
            );

            $notifications[] = [
                'contact_id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
                'sent_at' => now()->toIso8601String(),
                'method' => 'sms',
                'success' => $sent,
            ];
        }

        $this->update([
            'status' => 'notified',
            'notifications_sent' => $notifications,
            'contacts_notified_at' => now(),
        ]);
    }

    /**
     * Obtenir le message d'alerte
     */
    public function getAlertMessage(EmergencyContact $contact): string
    {
        $name = $this->user->name;
        $type = $this->getAlertTypeLabel();

        $message = "⚠️ ALERTE URGENCE ReziApp\n\n";
        $message .= "{$name} a déclenché une alerte ({$type}).\n";

        if ($this->message) {
            $message .= "\nMessage: {$this->message}\n";
        }

        if ($this->latitude && $this->longitude) {
            $message .= "\nLocalisation: https://maps.google.com/?q={$this->latitude},{$this->longitude}\n";
        }

        $message .= "\nContactez-le/la immédiatement.";

        return $message;
    }

    /**
     * Prendre en charge l'alerte
     */
    public function acknowledge(): void
    {
        $this->update(['status' => 'acknowledged']);
    }

    /**
     * Résoudre l'alerte
     */
    public function resolve(?int $resolverId = null, ?string $notes = null, bool $falseAlarm = false): void
    {
        $this->update([
            'status' => $falseAlarm ? 'false_alarm' : 'resolved',
            'resolved_by' => $resolverId ?? $this->user_id,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        // Désactiver le mode urgence
        $this->user->update(['emergency_mode' => false]);
    }

    /**
     * Obtenir le label du type d'alerte
     */
    public function getAlertTypeLabel(): string
    {
        return match($this->alert_type) {
            'panic' => 'Bouton panique',
            'sos' => 'SOS',
            'check_in_missed' => 'Check-in manqué',
            'suspicious' => 'Situation suspecte',
            'medical' => 'Urgence médicale',
            'other' => 'Autre',
            default => $this->alert_type,
        };
    }

    /**
     * Obtenir le label du statut
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'triggered' => 'Déclenché',
            'notified' => 'Contacts notifiés',
            'acknowledged' => 'Pris en charge',
            'resolved' => 'Résolu',
            'false_alarm' => 'Fausse alerte',
            default => $this->status,
        };
    }

    /**
     * Obtenir la couleur du statut
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'triggered' => 'red',
            'notified' => 'orange',
            'acknowledged' => 'yellow',
            'resolved' => 'green',
            'false_alarm' => 'gray',
            default => 'gray',
        };
    }
}
