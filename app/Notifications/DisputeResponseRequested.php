<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeResponseRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Dispute $dispute,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $deadline = $this->dispute->response_deadline?->format('d/m/Y à H:i') ?? '48 heures';

        return (new MailMessage())
            ->subject('⚠️ Réponse requise — Litige #'.$this->dispute->id)
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre réponse est requise concernant le litige **#{$this->dispute->id}** ({$this->dispute->type_label}).")
            ->line("**Date limite de réponse :** {$deadline}")
            ->line('Merci de fournir votre version des faits et toute preuve pertinente.')
            ->action('Répondre au litige', route('disputes.show', $this->dispute))
            ->line('Sans réponse dans le délai imparti, la décision sera prise sur la base des éléments disponibles.')
            ->salutation('L\'équipe Rezi App');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'dispute_id' => $this->dispute->id,
            'type' => 'dispute_response_requested',
        ];
    }
}
