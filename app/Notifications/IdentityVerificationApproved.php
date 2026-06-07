<?php

namespace App\Notifications;

use App\Models\IdentityVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IdentityVerificationApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected IdentityVerification $verification,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('✅ Identité vérifiée — Rezi App')
            ->greeting("Bonjour {$notifiable->name} !")
            ->line('Votre vérification d\'identité a été **approuvée** avec succès.')
            ->line("Votre niveau de confiance est maintenant : **{$notifiable->verification_level}**.")
            ->line('Vous pouvez désormais profiter de toutes les fonctionnalités de Rezi App.')
            ->action('Voir mon profil', route('verification.dashboard'))
            ->line('Merci de votre confiance !');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'identity_verification',
            'title' => 'Identité vérifiée ✅',
            'message' => 'Votre pièce d\'identité a été approuvée. Votre compte est maintenant vérifié.',
            'verification_id' => $this->verification->id,
            'action_url' => route('verification.dashboard'),
        ];
    }
}
