<?php

namespace App\Notifications;

use App\Models\FraudReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CriticalFraudAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected FraudReport $report,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $targetUser = $this->report->targetUser;

        return (new MailMessage())
            ->subject('🚨 ALERTE FRAUDE CRITIQUE — Score '.$this->report->risk_score)
            ->greeting('Alerte admin,')
            ->line('Un signalement de fraude **critique** a été détecté.')
            ->line("**Type :** {$this->report->fraud_type}")
            ->line("**Score de risque :** {$this->report->risk_score}/100")
            ->line('**Utilisateur ciblé :** '.($targetUser?->name ?? 'Inconnu')." (ID: {$this->report->target_user_id})")
            ->line('**Description :** '.($this->report->description ?? 'Aucune'))
            ->action('Voir le signalement', url('/admin/fraud-reports/'.$this->report->id))
            ->line('Action immédiate requise.')
            ->salutation('Système Rezi Studio Meublé Faya — Détection automatique');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'fraud_report_id' => $this->report->id,
            'risk_score' => $this->report->risk_score,
            'fraud_type' => $this->report->fraud_type,
            'type' => 'critical_fraud_alert',
        ];
    }
}
