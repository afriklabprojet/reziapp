<?php

namespace App\Notifications;

use App\Models\Referral;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralQualified extends Notification implements ShouldQueue
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
        $referredName = $this->referral->referred->name ?? 'Votre filleul';
        $reward = number_format($this->referral->referrer_reward ?? config('rezi.referral.referrer_reward', 5000), 0, ',', ' ');

        return (new MailMessage)
            ->subject('✅ Parrainage qualifié – Réclamez votre récompense !')
            ->greeting("Bonjour {$notifiable->name} !")
            ->line("**{$referredName}** a effectué sa première réservation confirmée sur REZI.")
            ->line("Votre parrainage est maintenant **qualifié**. Vous pouvez réclamer votre récompense de **{$reward} FCFA** dès maintenant.")
            ->action('Réclamer ma récompense', route('owner.marketing.referrals.index'))
            ->line('Merci d\'être un ambassadeur REZI !');
    }

    public function toArray(object $notifiable): array
    {
        $reward = number_format($this->referral->referrer_reward ?? config('rezi.referral.referrer_reward', 5000), 0, ',', ' ');

        return [
            'type' => 'referral_qualified',
            'referral_id' => $this->referral->id,
            'referred_name' => $this->referral->referred->name ?? 'Utilisateur',
            'reward_amount' => $this->referral->referrer_reward,
            'message' => 'Votre parrainage de ' . ($this->referral->referred->name ?? 'un utilisateur') . " est qualifié ! Réclamez vos {$reward} FCFA.",
            'url' => route('owner.marketing.referrals.index'),
        ];
    }
}
