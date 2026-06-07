<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DocumentExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Collection $documents,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->documents->count();
        $docList = $this->documents->map(function ($doc) {
            $expiryDate = $doc->expiry_date?->format('d/m/Y') ?? 'N/A';
            $daysLeft = $doc->expiry_date ? now()->diffInDays($doc->expiry_date, false) : 0;
            $status = $daysLeft <= 0 ? '❌ Expiré' : "⚠️ Expire dans {$daysLeft} jour(s)";

            return "• **{$doc->name}** — {$expiryDate} ({$status})";
        });

        $mail = (new MailMessage())
            ->subject("⚠️ {$count} document(s) expirent bientôt")
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Les documents suivants nécessitent votre attention :');

        foreach ($docList as $line) {
            $mail->line($line);
        }

        return $mail
            ->line('Veuillez mettre à jour vos documents pour maintenir votre profil à jour et continuer à recevoir des réservations.')
            ->action('Gérer mes documents', url('/owner/documents'))
            ->salutation('L\'équipe Rezi Studio Meublé Faya');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_expiry',
            'title' => 'Documents expirant bientôt',
            'message' => $this->documents->count().' document(s) expirent dans les 30 prochains jours.',
            'documents' => $this->documents->map(fn ($d) => [
                'name' => $d->name,
                'expiry_date' => $d->expiry_date?->format('d/m/Y'),
            ])->toArray(),
        ];
    }
}
