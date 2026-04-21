<?php

namespace App\Notifications;

use App\Models\Residence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResidenceRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Residence $residence,
        protected ?string $reason = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject('❌ Résidence non approuvée — '.$this->residence->name)
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre résidence **{$this->residence->name}** n'a pas été approuvée par notre équipe de modération.");

        if ($this->reason) {
            $mail->line("**Motif :** {$this->reason}");
        }

        return $mail
            ->line('Vous pouvez modifier votre annonce et la soumettre à nouveau.')
            ->action('Modifier ma résidence', route('owner.residences.edit', $this->residence))
            ->line('Si vous pensez qu\'il s\'agit d\'une erreur, contactez notre support.')
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'residence_id' => $this->residence->id,
            'residence_name' => $this->residence->name,
            'reason' => $this->reason,
            'type' => 'residence_rejected',
        ];
    }
}
