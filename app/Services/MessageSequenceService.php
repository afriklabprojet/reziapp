<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\MessageSequence;
use App\Models\MessageSequenceLog;
use App\Models\MessageSequenceStep;
use App\Models\User;
use App\Notifications\SequenceMessageNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MessageSequenceService
{
    /**
     * Planifier les messages d'une séquence pour une réservation
     */
    public function scheduleForBooking(Booking $booking, string $triggerEvent): void
    {
        $sequences = MessageSequence::active()
            ->forTrigger($triggerEvent)
            ->where(function ($q) use ($booking) {
                $q->where('user_id', $booking->residence->owner_id)
                    ->where(function ($sub) use ($booking) {
                        $sub->whereNull('residence_id')
                            ->orWhere('residence_id', $booking->residence_id);
                    });
            })
            ->with('steps')
            ->get();

        foreach ($sequences as $sequence) {
            foreach ($sequence->steps as $step) {
                if (!$step->is_active) {
                    continue;
                }

                $scheduledAt = $this->calculateScheduledTime($step, $booking);

                if ($scheduledAt && $scheduledAt->isFuture()) {
                    MessageSequenceLog::create([
                        'message_sequence_id' => $sequence->id,
                        'step_id'             => $step->id,
                        'booking_id'          => $booking->id,
                        'user_id'             => $booking->user_id,
                        'channel'             => $step->channel,
                        'status'              => 'pending',
                        'scheduled_at'        => $scheduledAt,
                    ]);
                }
            }
        }
    }

    /**
     * Envoyer les messages planifiés prêts
     */
    public function sendPendingMessages(): int
    {
        $readyLogs = MessageSequenceLog::ready()
            ->with(['step', 'booking.residence', 'booking.user', 'recipient'])
            ->limit(100)
            ->get();

        $sent = 0;
        foreach ($readyLogs as $log) {
            try {
                $this->sendMessage($log);
                $sent++;
            } catch (\Throwable $e) {
                $log->markFailed($e->getMessage());
                Log::error("MessageSequence failed: {$e->getMessage()}", [
                    'log_id'     => $log->id,
                    'booking_id' => $log->booking_id,
                ]);
            }
        }

        return $sent;
    }

    /**
     * Annuler les messages planifiés pour une réservation
     */
    public function cancelForBooking(int $bookingId): void
    {
        MessageSequenceLog::where('booking_id', $bookingId)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }

    /**
     * Calculer le moment d'envoi d'un step
     */
    private function calculateScheduledTime(MessageSequenceStep $step, Booking $booking): ?Carbon
    {
        return match ($step->delay_reference) {
            'after_trigger'   => now()->addHours($step->delay_hours),
            'before_checkin'  => Carbon::parse($booking->check_in)->subHours($step->delay_hours),
            'after_checkout'  => Carbon::parse($booking->check_out)->addHours($step->delay_hours),
            'before_checkout' => Carbon::parse($booking->check_out)->subHours($step->delay_hours),
            default           => now()->addHours($step->delay_hours),
        };
    }

    /**
     * Envoyer un message individuel
     */
    private function sendMessage(MessageSequenceLog $log): void
    {
        $step    = $log->step;
        $booking = $log->booking;

        if (!$booking || !$step) {
            $log->markFailed('Booking or step missing');

            return;
        }

        $data = [
            'guest_name'     => $booking->user->name ?? 'Voyageur',
            'residence_name' => $booking->residence->name ?? '',
            'check_in_date'  => $booking->check_in?->format('d/m/Y') ?? '',
            'check_out_date' => $booking->check_out?->format('d/m/Y') ?? '',
            'owner_name'     => $booking->residence->owner->name ?? '',
            'booking_ref'    => $booking->reference ?? '',
        ];

        $renderedMessage = $step->renderMessage($data);
        $subject = $step->subject ? str_replace(
            array_map(fn ($k) => '{'.$k.'}', array_keys($data)),
            array_values($data),
            $step->subject,
        ) : 'Message de '.($booking->residence->name ?? 'ReziApp');

        // Envoyer selon le canal
        match ($step->channel) {
            'email'    => $this->sendEmailMessage($log->recipient, $subject, $renderedMessage),
            'sms'      => $this->sendSmsMessage($log->recipient, $renderedMessage),
            'whatsapp' => $this->sendWhatsappMessage($log->recipient, $renderedMessage),
            'in_app'   => $this->sendInAppMessage($log->recipient, $subject, $renderedMessage),
            default    => $this->sendEmailMessage($log->recipient, $subject, $renderedMessage),
        };

        $log->markSent();
    }

    private function sendEmailMessage(User $user, string $subject, string $message): void
    {
        $user->notify(new SequenceMessageNotification($subject, $message, 'email'));
    }

    private function sendSmsMessage(User $user, string $message): void
    {
        if (app()->bound(SmsService::class)) {
            app(SmsService::class)->send($user->phone, $message);
        }
    }

    private function sendWhatsappMessage(User $user, string $message): void
    {
        if (app()->bound(WhatsAppService::class)) {
            app(WhatsAppService::class)->sendText($user->phone, $message);
        }
    }

    private function sendInAppMessage(User $user, string $subject, string $message): void
    {
        $user->notify(new SequenceMessageNotification($subject, $message, 'database'));
    }

    /**
     * Créer des séquences par défaut pour un propriétaire
     */
    public function createDefaultSequences(User $owner): void
    {
        $defaults = [
            [
                'name'          => 'Bienvenue après réservation',
                'trigger_event' => MessageSequence::TRIGGER_BOOKING_CONFIRMED,
                'steps'         => [
                    ['delay_hours' => 0, 'delay_reference' => 'after_trigger', 'channel' => 'email',
                     'subject' => 'Réservation confirmée — {residence_name}',
                     'message' => "Bonjour {guest_name},\n\nVotre réservation à {residence_name} est confirmée !\nArrivée : {check_in_date}\nDépart : {check_out_date}\n\nNous reviendrons vers vous avec les détails d'accès.\n\nCordialement,\n{owner_name}"],
                ],
            ],
            [
                'name'          => 'Instructions d\'arrivée',
                'trigger_event' => MessageSequence::TRIGGER_CHECK_IN_APPROACHING,
                'steps'         => [
                    ['delay_hours' => 48, 'delay_reference' => 'before_checkin', 'channel' => 'email',
                     'subject' => 'Préparez votre arrivée — {residence_name}',
                     'message' => "Bonjour {guest_name},\n\nVotre arrivée approche ! Voici les informations pratiques :\n\n📍 Adresse : consultez votre espace réservation\n🔑 Instructions d'accès : à venir la veille\n\nN'hésitez pas à nous contacter pour toute question.\n\nÀ très bientôt !\n{owner_name}"],
                    ['delay_hours' => 4, 'delay_reference' => 'before_checkin', 'channel' => 'whatsapp',
                     'subject' => null,
                     'message' => 'Bonjour {guest_name} ! Bienvenue à {residence_name}. Votre logement est prêt. Bonne arrivée ! 🏠'],
                ],
            ],
            [
                'name'          => 'Demande d\'avis post-séjour',
                'trigger_event' => MessageSequence::TRIGGER_POST_CHECKOUT,
                'steps'         => [
                    ['delay_hours' => 24, 'delay_reference' => 'after_checkout', 'channel' => 'email',
                     'subject' => 'Comment était votre séjour à {residence_name} ?',
                     'message' => "Bonjour {guest_name},\n\nMerci d'avoir séjourné à {residence_name} !\n\nVotre avis compte beaucoup pour nous. Prenez un moment pour partager votre expérience.\n\nMerci et à bientôt !\n{owner_name}"],
                ],
            ],
            [
                'name'          => 'Rappel départ',
                'trigger_event' => MessageSequence::TRIGGER_PRE_CHECKOUT,
                'steps'         => [
                    ['delay_hours' => 18, 'delay_reference' => 'before_checkout', 'channel' => 'email',
                     'subject' => 'Rappel : départ de {residence_name} demain',
                     'message' => "Bonjour {guest_name},\n\nVotre séjour à {residence_name} touche à sa fin.\n\nRappel :\n- Date de départ : {check_out_date}\n- Merci de laisser les clés à l'endroit convenu\n\nNous espérons que vous avez passé un agréable séjour !\n{owner_name}"],
                ],
            ],
        ];

        foreach ($defaults as $seq) {
            $sequence = MessageSequence::create([
                'user_id'       => $owner->id,
                'name'          => $seq['name'],
                'trigger_event' => $seq['trigger_event'],
                'is_active'     => true,
            ]);

            foreach ($seq['steps'] as $i => $stepData) {
                $sequence->steps()->create(array_merge($stepData, ['step_order' => $i + 1]));
            }
        }
    }
}
