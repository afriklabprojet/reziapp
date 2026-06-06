<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Residence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuestBookingConfirmation extends Notification implements ShouldQueue
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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $checkIn = $this->bookingRequest->check_in->format('d/m/Y');
        $checkOut = $this->bookingRequest->check_out->format('d/m/Y');
        $guests = $this->bookingRequest->guests;
        $setPasswordUrl = route('guest.set-password', [
            'token' => $notifiable->guest_token,
            'email' => $notifiable->email,
        ]);

        $isPaid = $this->bookingRequest instanceof Booking && $this->bookingRequest->payment_status === 'paid';

        return (new MailMessage())
            ->subject(($isPaid ? '✅ Réservation confirmée' : '✅ Demande de réservation envoyée')." — {$this->residence->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->when($isPaid, fn ($mail) => $mail->line("Votre réservation a été confirmée et payée pour **{$this->residence->name}**."))
            ->when(!$isPaid, fn ($mail) => $mail->line("Votre demande de réservation a bien été envoyée pour **{$this->residence->name}**."))
            ->line('**Récapitulatif :**')
            ->line("• Arrivée : {$checkIn}")
            ->line("• Départ : {$checkOut}")
            ->line("• Voyageurs : {$guests}")
            ->when(!$isPaid, fn ($mail) => $mail->line('Le propriétaire a 48h pour accepter ou refuser votre demande.'))
            ->line('---')
            ->line('**Un compte temporaire a été créé pour vous.**')
            ->line('Pour suivre votre réservation et accéder à toutes les fonctionnalités, créez votre mot de passe :')
            ->action('Créer mon mot de passe', $setPasswordUrl)
            ->line('Ce lien est valable 7 jours.')
            ->salutation('L\'équipe ReziApp');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'guest_booking_confirmation',
            'booking_request_id' => $this->bookingRequest->id,
            'residence_id' => $this->residence->id,
            'residence_name' => $this->residence->name,
        ];
    }
}
