<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\OwnerAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OwnerAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private OwnerAlert $alert,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'    => $this->alert->title,
            'message'  => $this->alert->message,
            'type'     => 'owner_alert',
            'severity' => $this->alert->severity,
            'url'      => $this->alert->action_url ?? route('owner.alerts.index'),
        ];
    }
}
