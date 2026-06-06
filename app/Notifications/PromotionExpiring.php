<?php

namespace App\Notifications;

use App\Models\Promotion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromotionExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Promotion $promotion,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiresAt = $this->promotion->ends_at->format('d/m/Y à H:i');
        $residenceName = $this->promotion->residence?->name ?? 'votre résidence';

        return (new MailMessage())
            ->subject("⏰ Promotion bientôt expirée — {$this->promotion->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre promotion **\"{$this->promotion->title}\"** pour **{$residenceName}** expire le **{$expiresAt}**.")
            ->line("**Résultats :** {$this->promotion->uses_count} utilisation(s) jusqu'à présent.")
            ->action('Gérer mes promotions', route('owner.marketing.promotions.index'))
            ->line('Vous pouvez la prolonger ou en créer une nouvelle.')
            ->salutation('L\'équipe ReziApp');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'promotion_expiring',
            'promotion_id' => $this->promotion->id,
            'promotion_title' => $this->promotion->title,
            'residence_name' => $this->promotion->residence?->name,
            'expires_at' => $this->promotion->ends_at->toDateTimeString(),
            'message' => 'Promotion "'.$this->promotion->title.'" expire bientôt',
        ];
    }
}
