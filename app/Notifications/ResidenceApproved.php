<?php

namespace App\Notifications;

use App\Models\Residence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResidenceApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Residence $residence,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('✅ Votre résidence a été approuvée — '.$this->residence->name)
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Bonne nouvelle ! Votre résidence **{$this->residence->name}** a été approuvée par notre équipe de modération.")
            ->line('Elle est désormais visible par tous les utilisateurs sur Rezi Studio Meublé Faya.')
            ->action('Voir ma résidence', route('residences.show', $this->residence))
            ->line('Pensez à vérifier que vos photos, tarifs et disponibilités sont à jour.')
            ->salutation('L\'équipe Rezi Studio Meublé Faya');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'residence_id' => $this->residence->id,
            'residence_name' => $this->residence->name,
            'type' => 'residence_approved',
        ];
    }
}
