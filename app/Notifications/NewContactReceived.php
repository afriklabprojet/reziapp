<?php

namespace App\Notifications;

use App\Models\Contact;
use App\Models\Residence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Contact $contact,
        protected Residence $residence,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $guestName = $this->contact->user?->name ?? 'Un visiteur';

        return (new MailMessage())
            ->subject("📩 Nouveau contact pour {$this->residence->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("**{$guestName}** souhaite vous contacter à propos de votre résidence **{$this->residence->name}**.")
            ->line("Message : \"{$this->contact->message}\"")
            ->action('Voir le contact', route('owner.contacts.show', $this->contact))
            ->line('Répondez rapidement pour augmenter votre taux de conversion !')
            ->salutation('L\'équipe ReziApp');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_contact',
            'contact_id' => $this->contact->id,
            'residence_id' => $this->residence->id,
            'residence_name' => $this->residence->name,
            'guest_name' => $this->contact->user?->name ?? 'Visiteur',
            'message' => 'Nouveau contact pour '.$this->residence->name,
        ];
    }
}
