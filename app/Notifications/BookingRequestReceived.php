<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Residence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRequestReceived extends Notification implements ShouldQueue
{
    use Queueable;

    protected Booking|BookingRequest $bookingRequest;
    protected Residence $residence;

    public function __construct(
        Booking|BookingRequest $bookingRequest,
        Residence $residence,
    ) {
        $this->bookingRequest = $bookingRequest;
        $this->residence = $residence;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $guestName = $this->bookingRequest->user?->name ?? 'Un client';
        $checkIn = $this->bookingRequest->check_in->format('d/m/Y');
        $checkOut = $this->bookingRequest->check_out->format('d/m/Y');
        $guests = $this->bookingRequest->guests;
        $message = $this->bookingRequest instanceof Booking
            ? $this->bookingRequest->guest_message
            : $this->bookingRequest->message;

        $isPaid = $this->bookingRequest instanceof Booking && $this->bookingRequest->payment_status === 'paid';

        return (new MailMessage())
            ->subject('📋 Demande de réservation'.($isPaid ? ' (payée)' : '')." — {$this->residence->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("**{$guestName}** souhaite réserver votre résidence **{$this->residence->name}**.")
            ->when($isPaid, fn ($mail) => $mail->line('✅ **Le client a déjà payé.**'))
            ->line('**Dates demandées :**')
            ->line("• Arrivée : {$checkIn}")
            ->line("• Départ : {$checkOut}")
            ->line("• Voyageurs : {$guests}")
            ->when($message, function ($mail) use ($message) {
                return $mail->line("**Message :** \"{$message}\"");
            })
            ->action('Répondre à la demande', route('owner.bookings.requests'))
            ->line('Vous avez 48h pour accepter ou refuser cette demande.')
            ->salutation('L\'équipe Rezi App');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_request',
            'booking_request_id' => $this->bookingRequest->id,
            'residence_id' => $this->residence->id,
            'residence_name' => $this->residence->name,
            'guest_name' => $this->bookingRequest->user?->name ?? 'Client',
            'check_in' => $this->bookingRequest->check_in->toDateString(),
            'check_out' => $this->bookingRequest->check_out->toDateString(),
            'message' => 'Demande de réservation pour '.$this->residence->name,
        ];
    }
}
