<?php

namespace App\Notifications;

use App\Models\CoHost;
use App\Models\Residence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoHostInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected CoHost $coHost,
        protected Residence $residence,
    ) {
    }

    /**
     * Canaux de notification
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Contenu de l'email
     */
    public function toMail(object $notifiable): MailMessage
    {
        $ownerName = $this->residence->owner?->name ?? 'Un propriétaire';
        $residenceName = $this->residence->name;
        $acceptUrl = route('cohost.invitation', $this->coHost->invitation_token);

        $permissions = collect([
            'can_manage_calendar' => 'Gérer le calendrier',
            'can_respond_messages' => 'Répondre aux messages',
            'can_accept_bookings' => 'Accepter les réservations',
            'can_edit_listing' => 'Modifier l\'annonce',
            'can_manage_pricing' => 'Gérer les tarifs',
            'can_view_earnings' => 'Voir les revenus',
        ])->filter(fn ($label, $key) => $this->coHost->{$key})->values();

        return (new MailMessage())
            ->subject("Invitation co-hôte — {$residenceName}")
            ->greeting("Bonjour {$this->coHost->name},")
            ->line("{$ownerName} vous invite à devenir co-hôte de la résidence **{$residenceName}** sur Rezi Studio Meublé Faya.")
            ->line('**Permissions accordées :**')
            ->line($permissions->map(fn ($p) => "• {$p}")->implode("\n"))
            ->action('Accepter l\'invitation', $acceptUrl)
            ->line('Cette invitation expire dans 7 jours.')
            ->line('Si vous ne connaissez pas cette personne, vous pouvez ignorer cet email.')
            ->salutation('L\'équipe Rezi Studio Meublé Faya');
    }

    /**
     * Représentation en tableau
     */
    public function toArray(object $notifiable): array
    {
        return [
            'cohost_id' => $this->coHost->id,
            'residence_id' => $this->residence->id,
            'residence_name' => $this->residence->name,
        ];
    }
}
