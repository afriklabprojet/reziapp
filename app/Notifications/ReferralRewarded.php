<?php

namespace App\Notifications;

use App\Models\Referral;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralRewarded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Referral $referral,
        protected string $recipientType = 'referrer', // 'referrer' or 'referred'
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->recipientType === 'referrer') {
            $reward = number_format($this->referral->referrer_reward, 0, ',', ' ');
            $referredName = $this->referral->referred->name ?? 'votre filleul';

            return (new MailMessage())
                ->subject("💰 {$reward} FCFA crédités sur votre compte !")
                ->greeting("Bonjour {$notifiable->name} !")
                ->line("Votre récompense de parrainage de **{$reward} FCFA** a été créditée sur votre solde de parrainage.")
                ->line("Merci d'avoir parrainé **{$referredName}** sur Rezi App.")
                ->action('Voir mon solde', route('owner.marketing.referrals.index'))
                ->line('Continuez à parrainer vos amis pour gagner encore plus !');
        }

        $reward = number_format($this->referral->referred_reward, 0, ',', ' ');
        $referrerName = $this->referral->referrer->name ?? 'votre parrain';

        return (new MailMessage())
            ->subject("🎁 {$reward} FCFA de bienvenue crédités !")
            ->greeting("Bonjour {$notifiable->name} !")
            ->line("Grâce au parrainage de **{$referrerName}**, vous avez reçu **{$reward} FCFA** sur votre solde.")
            ->line('Ce montant sera automatiquement appliqué à votre prochaine réservation.')
            ->action('Explorer les résidences', url('/'))
            ->line('Bienvenue sur Rezi App !');
    }

    public function toArray(object $notifiable): array
    {
        $isReferrer = $this->recipientType === 'referrer';
        $reward = $isReferrer ? $this->referral->referrer_reward : $this->referral->referred_reward;
        $formattedReward = number_format($reward, 0, ',', ' ');

        return [
            'type' => 'referral_rewarded',
            'referral_id' => $this->referral->id,
            'recipient_type' => $this->recipientType,
            'reward_amount' => $reward,
            'message' => "{$formattedReward} FCFA crédités sur votre solde de parrainage !",
            'url' => $isReferrer ? route('owner.marketing.referrals.index') : url('/'),
        ];
    }
}
