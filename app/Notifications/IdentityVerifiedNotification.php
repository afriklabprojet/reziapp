<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IdentityVerifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('✅ Votre identité a été vérifiée - REZI')
            ->greeting("Bonjour {$notifiable->name} !")
            ->line('Bonne nouvelle ! Votre identité a été vérifiée avec succès.')
            ->line('Vous avez maintenant accès au badge "Identité vérifiée" qui sera affiché sur votre profil.')
            ->line('Ce badge renforce la confiance des locataires potentiels et peut augmenter vos chances de recevoir des réservations.')
            ->action('Voir mon profil', url('/owner/profile'))
            ->line('Merci de faire partie de la communauté REZI !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'identity_verified',
            'title' => 'Identité vérifiée',
            'message' => 'Votre identité a été vérifiée avec succès. Vous avez obtenu le badge "Identité vérifiée".',
            'icon' => 'heroicon-o-check-badge',
            'color' => 'success',
        ];
    }
}
