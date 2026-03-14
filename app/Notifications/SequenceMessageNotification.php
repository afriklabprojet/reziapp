<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SequenceMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $subject,
        private string $messageContent,
        private string $channel = 'mail',
    ) {}

    public function via(object $notifiable): array
    {
        return match ($this->channel) {
            'database' => ['database'],
            default    => ['mail'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting('Bonjour ' . ($notifiable->name ?? '') . ',')
            ->line($this->messageContent)
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => $this->subject,
            'message' => $this->messageContent,
            'type'    => 'sequence_message',
        ];
    }
}
