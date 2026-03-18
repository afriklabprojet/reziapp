<?php

namespace App\Notifications;

use App\Models\SponsoredListing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SponsoredListingCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected SponsoredListing $sponsoredListing,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $residenceName = $this->sponsoredListing->residence?->name ?? 'votre résidence';
        $typeLabel = $this->sponsoredListing->type_label;
        $impressions = number_format($this->sponsoredListing->impressions, 0, ',', ' ');
        $clicks = number_format($this->sponsoredListing->clicks, 0, ',', ' ');
        $contacts = $this->sponsoredListing->contacts_generated;
        $amountSpent = number_format($this->sponsoredListing->amount_spent, 0, ',', ' ');
        $ctr = $this->sponsoredListing->click_rate;
        $conversionRate = $this->sponsoredListing->conversion_rate;

        $startDate = $this->sponsoredListing->starts_at?->format('d/m/Y') ?? 'N/A';
        $endDate = $this->sponsoredListing->ends_at?->format('d/m/Y') ?? 'N/A';

        return (new MailMessage())
            ->subject("📊 Rapport de campagne terminée — {$residenceName}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre campagne **{$typeLabel}** pour **{$residenceName}** est terminée.")
            ->line("**Période :** du {$startDate} au {$endDate}")
            ->line('')
            ->line("**📊 Rapport de performance :**")
            ->line("• Impressions : **{$impressions}**")
            ->line("• Clics : **{$clicks}**")
            ->line("• Taux de clic (CTR) : **{$ctr}%**")
            ->line("• Contacts générés : **{$contacts}**")
            ->line("• Taux de conversion : **{$conversionRate}%**")
            ->line("• Budget dépensé : **{$amountSpent} FCFA**")
            ->action('Voir les détails', route('owner.marketing.sponsored.show', $this->sponsoredListing))
            ->line('Relancez une nouvelle campagne pour maintenir votre visibilité !')
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sponsored_listing_completed',
            'sponsored_listing_id' => $this->sponsoredListing->id,
            'residence_id' => $this->sponsoredListing->residence_id,
            'residence_name' => $this->sponsoredListing->residence?->name,
            'impressions' => $this->sponsoredListing->impressions,
            'clicks' => $this->sponsoredListing->clicks,
            'contacts' => $this->sponsoredListing->contacts_generated,
            'amount_spent' => $this->sponsoredListing->amount_spent,
            'message' => 'Campagne "' . $this->sponsoredListing->type_label . '" terminée pour ' . ($this->sponsoredListing->residence?->name ?? 'résidence'),
        ];
    }
}
