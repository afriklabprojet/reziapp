<?php

namespace App\Notifications;

use App\Models\EmergencyAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmergencyAlertTriggered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected EmergencyAlert $alert,
    ) {
        // Les urgences passent en priorité haute dans la queue
        $this->onQueue('high');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $user = $this->alert->user;
        $type = strtoupper($this->alert->alert_type);
        $location = $this->alert->latitude && $this->alert->longitude
            ? "https://www.google.com/maps?q={$this->alert->latitude},{$this->alert->longitude}"
            : null;

        $mail = (new MailMessage())
            ->subject("🆘 URGENCE {$type} — Utilisateur {$user->name}")
            ->greeting('ALERTE URGENCE')
            ->line("L'utilisateur **{$user->name}** (ID: {$user->id}) a déclenché une alerte **{$type}**.")
            ->line('**Date :** '.$this->alert->created_at->format('d/m/Y à H:i:s'));

        if ($this->alert->message) {
            $mail->line("**Message :** {$this->alert->message}");
        }

        if ($location) {
            $mail->action('📍 Voir la localisation', $location);
        }

        return $mail
            ->line('**Téléphone :** '.($user->phone ?? 'Non renseigné'))
            ->line('Action immédiate requise. Les contacts d\'urgence de l\'utilisateur ont été notifiés.')
            ->salutation('Système ReziApp — Alerte automatique');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'user_id' => $this->alert->user_id,
            'alert_type' => $this->alert->alert_type,
            'type' => 'emergency_alert',
        ];
    }
}
