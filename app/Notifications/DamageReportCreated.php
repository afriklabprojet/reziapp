<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\DamageReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DamageReportCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private DamageReport $report,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Nouveau rapport de dommage — '.$this->report->reference)
            ->greeting('Bonjour,')
            ->line("Un rapport de dommage a été créé pour {$this->report->residence->name}.")
            ->line('Catégorie : '.(DamageReport::CATEGORIES[$this->report->category] ?? $this->report->category))
            ->line('Gravité : '.(DamageReport::SEVERITIES[$this->report->severity] ?? $this->report->severity))
            ->action('Voir le rapport', route('owner.damages.show', $this->report))
            ->salutation('L\'équipe ReziApp');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'Rapport de dommage: '.$this->report->reference,
            'message' => "Dommage signalé sur {$this->report->residence->name}",
            'type'    => 'damage_report',
            'url'     => route('owner.damages.show', $this->report),
        ];
    }
}
