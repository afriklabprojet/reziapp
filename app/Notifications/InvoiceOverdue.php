<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Invoice $invoice,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) $this->invoice->total, 0, ',', ' ');
        $dueDate = $this->invoice->due_date?->format('d/m/Y') ?? 'N/A';
        $daysOverdue = $this->invoice->due_date
            ? now()->diffInDays($this->invoice->due_date)
            : 0;

        return (new MailMessage())
            ->subject("⚠️ Facture en retard — {$this->invoice->invoice_number}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre facture **{$this->invoice->invoice_number}** de **{$amount} FCFA** est en retard de paiement.")
            ->line('**Détails :**')
            ->line("• Numéro : {$this->invoice->invoice_number}")
            ->line("• Montant : {$amount} FCFA")
            ->line("• Échéance : {$dueDate}")
            ->line("• Retard : {$daysOverdue} jour(s)")
            ->line('Nous vous prions de régulariser votre situation dans les plus brefs délais afin d\'éviter des pénalités.')
            ->action('Voir ma facture', url('/'))
            ->salutation('L\'équipe Rezi App');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_overdue',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total,
            'due_date' => $this->invoice->due_date?->toDateString(),
            'message' => 'Facture '.$this->invoice->invoice_number.' en retard de paiement',
        ];
    }
}
