<?php

namespace App\Notifications;

use App\Models\SponsoredListing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SponsoredListingExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected SponsoredListing $sponsoredListing,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiresAt = $this->sponsoredListing->ends_at->format('d/m/Y');
        $residenceName = $this->sponsoredListing->residence?->name ?? 'votre résidence';
        $typeLabel = $this->sponsoredListing->type_label;
        $impressions = number_format($this->sponsoredListing->impressions, 0, ',', ' ');
        $clicks = number_format($this->sponsoredListing->clicks, 0, ',', ' ');

        return (new MailMessage())
            ->subject("⭐ Mise en avant bientôt terminée — {$residenceName}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre mise en avant **{$typeLabel}** pour **{$residenceName}** se termine le **{$expiresAt}**.")
            ->line("**Performance :**")
            ->line("• Impressions : {$impressions}")
            ->line("• Clics : {$clicks}")
            ->line("• Contacts générés : {$this->sponsoredListing->contacts_generated}")
            ->action('Renouveler la mise en avant', route('owner.marketing.sponsored.create'))
            ->line('Renouvelez pour maintenir votre visibilité !')
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sponsored_listing_expiring',
            'sponsored_listing_id' => $this->sponsoredListing->id,
            'residence_id' => $this->sponsoredListing->residence_id,
            'residence_name' => $this->sponsoredListing->residence?->name,
            'expires_at' => $this->sponsoredListing->ends_at->toDateTimeString(),
            'message' => 'Mise en avant de ' . ($this->sponsoredListing->residence?->name ?? 'résidence') . ' expire bientôt',
        ];
    }
}
