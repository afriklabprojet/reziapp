<?php

namespace App\Notifications;

use App\Models\IdentityVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IdentityVerificationRejected extends Notification implements ShouldQueue
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
        $mail = (new MailMessage())
            ->subject('❌ Vérification d\'identité rejetée — REZI')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Votre demande de vérification d\'identité a été **rejetée**.');

        if ($this->verification->rejection_reason) {
            $mail->line("**Motif :** {$this->verification->rejection_reason}");
        }

        if ($this->verification->canRetry()) {
            $mail->line('Vous pouvez soumettre une nouvelle demande avec des documents corrects.')
                ->action('Réessayer', route('verification.identity.start'));
        } else {
            $mail->line('Vous avez atteint le nombre maximum de tentatives. Contactez le support pour plus d\'aide.')
                ->action('Contacter le support', route('verification.dashboard'));
        }

        return $mail->line('L\'équipe REZI');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'identity_verification',
            'title' => 'Vérification rejetée ❌',
            'message' => $this->verification->rejection_reason
                ? "Motif : {$this->verification->rejection_reason}"
                : 'Votre vérification d\'identité a été rejetée.',
            'verification_id' => $this->verification->id,
            'action_url' => route('verification.identity.start'),
        ];
    }
}
