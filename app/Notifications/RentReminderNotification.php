<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\RentReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private RentReminder $reminder,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isOverdue = $this->reminder->isOverdue();
        $subject   = $isOverdue
            ? 'Rappel urgent : Loyer en retard'
            : 'Rappel : Loyer à venir';

        return (new MailMessage())
            ->subject($subject.' — '.$this->reminder->residence?->name)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($isOverdue
                ? 'Votre loyer de '.number_format($this->reminder->amount, 0, ',', ' ').' FCFA est en retard.'
                : 'Votre loyer de '.number_format($this->reminder->amount, 0, ',', ' ').' FCFA arrive à échéance le '.$this->reminder->due_date->translatedFormat('d F Y').'.')
            ->line('Résidence : '.($this->reminder->residence?->name ?? 'N/A'))
            ->action('Voir mes paiements', url('/'))
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'rent_reminder',
            'reminder_id'  => $this->reminder->id,
            'amount'       => $this->reminder->amount,
            'due_date'     => $this->reminder->due_date->toDateString(),
            'residence'    => $this->reminder->residence?->name,
            'is_overdue'   => $this->reminder->isOverdue(),
        ];
    }
}
