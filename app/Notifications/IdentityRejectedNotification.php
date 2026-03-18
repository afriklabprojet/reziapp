<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IdentityRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $reason)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('❌ Vérification d\'identité non approuvée - REZI')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Nous n\'avons malheureusement pas pu valider votre vérification d\'identité.')
            ->line('**Raison :** ' . $this->reason)
            ->line('Vous pouvez soumettre de nouveaux documents pour une nouvelle vérification.')
            ->line('**Conseils pour une vérification réussie :**')
            ->line('- Assurez-vous que les photos sont nettes et lisibles')
            ->line('- Le document doit être en cours de validité')
            ->line('- Toutes les informations doivent être visibles')
            ->action('Soumettre de nouveaux documents', url('/owner/verification'))
            ->line('Si vous avez des questions, n\'hésitez pas à nous contacter.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'identity_rejected',
            'title' => 'Vérification non approuvée',
            'message' => "Votre vérification d'identité n'a pas été approuvée. Raison: {$this->reason}",
            'icon' => 'heroicon-o-x-circle',
            'color' => 'danger',
            'action_url' => '/owner/verification',
            'action_label' => 'Réessayer',
        ];
    }
}
