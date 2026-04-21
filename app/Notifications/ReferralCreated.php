<?php

namespace App\Notifications;

use App\Models\Referral;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Referral $referral,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $referredName = $this->referral->referred->name ?? 'Un utilisateur';

        return (new MailMessage())
            ->subject('🎉 Nouveau filleul inscrit !')
            ->greeting("Bonjour {$notifiable->name} !")
            ->line("**{$referredName}** s'est inscrit(e) sur REZI grâce à votre code de parrainage.")
            ->line('Votre parrainage est en attente. Il sera qualifié dès que votre filleul effectuera sa première réservation confirmée.')
            ->action('Voir mes parrainages', route('owner.marketing.referrals.index'))
            ->line('Continuez à partager votre code pour gagner encore plus de récompenses !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'referral_created',
            'referral_id' => $this->referral->id,
            'referred_name' => $this->referral->referred->name ?? 'Utilisateur',
            'message' => ($this->referral->referred->name ?? 'Un utilisateur').' s\'est inscrit(e) grâce à votre parrainage.',
            'url' => route('owner.marketing.referrals.index'),
        ];
    }
}
