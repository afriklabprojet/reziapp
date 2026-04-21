<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoyaltyTierUpgraded extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param string $newTier  Clé du nouveau palier ('bronze', 'silver', 'gold', 'platinum')
     * @param string $oldTier  Clé de l'ancien palier
     */
    public function __construct(
        protected string $newTier,
        protected string $oldTier,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tiers   = User::LOYALTY_TIERS;
        $config  = $tiers[$this->newTier] ?? ['label' => $this->newTier, 'icon' => '🏅', 'discount' => 0];
        $oldConf = $tiers[$this->oldTier] ?? ['label' => $this->oldTier, 'icon' => '⭐'];

        $label    = $config['label'];
        $icon     = $config['icon'];
        $discount = $config['discount'];
        $oldLabel = $oldConf['label'];

        return (new MailMessage())
            ->subject("{$icon} Félicitations ! Vous avez atteint le niveau {$label}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('Bonne nouvelle ! Votre fidélité a été récompensée.')
            ->line("Vous passez du niveau **{$oldLabel}** au niveau **{$icon} {$label}** !")
            ->line("En tant que membre **{$label}**, vous bénéficiez maintenant de :")
            ->line("• **{$discount} % de réduction** sur toutes vos réservations REZI")
            ->line('• Accès prioritaire aux nouvelles résidences')
            ->line('• Support client dédié')
            ->action('Découvrir mes avantages', route('client.dashboard'))
            ->line('Merci de faire confiance à REZI pour vos séjours en Côte d\'Ivoire.')
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        $tiers  = User::LOYALTY_TIERS;
        $config = $tiers[$this->newTier] ?? ['label' => $this->newTier, 'icon' => '🏅', 'discount' => 0];

        return [
            'type'      => 'loyalty_tier_upgraded',
            'new_tier'  => $this->newTier,
            'old_tier'  => $this->oldTier,
            'label'     => $config['label'],
            'icon'      => $config['icon'],
            'discount'  => $config['discount'],
            'message'   => "Félicitations ! Vous atteignez le niveau {$config['label']} {$config['icon']}",
        ];
    }
}
