<?php

namespace App\Services;

use App\Models\Message;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Models\User;
use App\Support\SensitiveData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class NotificationService
{
    private const ALLOWED_PUSH_HOST_SUFFIXES = [
        'fcm.googleapis.com',
        'push.services.mozilla.com',
        'updates.push.services.mozilla.com',
        'push.apple.com',
        'notify.windows.com',
    ];

    public function __construct(
        private readonly ?PublicUrlGuard $publicUrlGuard = null,
    ) {
    }

    /**
     * Envoyer une notification de nouveau message
     */
    public function sendMessageNotification(Message $message, User $recipient): void
    {
        $preference = $this->getPreferences($recipient);

        // Vérifier les heures silencieuses
        if ($preference->isQuietHours()) {
            return;
        }

        $title = 'Nouveau message';
        $body = $this->truncate($message->content, 100);
        $sender = $message->sender;

        if ($sender) {
            $title = "Message de {$sender->name}";
        }

        $data = [
            'type' => NotificationLog::TYPE_MESSAGE,
            'conversation_id' => $message->conversation_id,
            'message_id' => $message->id,
            'url' => route('chat.show', $message->conversation_id),
        ];

        $this->send($recipient, NotificationLog::TYPE_MESSAGE, $title, $body, $data);
    }

    /**
     * Envoyer une notification de demande de visite
     */
    public function sendVisitRequestNotification(User $recipient, array $data): void
    {
        $title = 'Nouvelle demande de visite';
        $body = "Vous avez reçu une demande de visite pour {$data['residence_name']}";

        $notifData = [
            'type' => NotificationLog::TYPE_VISIT_REQUEST,
            'visit_id' => $data['visit_id'] ?? null,
            'residence_id' => $data['residence_id'] ?? null,
            'url' => $data['url'] ?? route('owner.visits.index'),
        ];

        $this->send($recipient, NotificationLog::TYPE_VISIT_REQUEST, $title, $body, $notifData);
    }

    /**
     * Envoyer une notification de visite confirmée
     */
    public function sendVisitConfirmedNotification(User $recipient, array $data): void
    {
        $title = 'Visite confirmée';
        $body = "Votre visite pour {$data['residence_name']} a été confirmée pour le {$data['date']}";

        $notifData = [
            'type' => NotificationLog::TYPE_VISIT_CONFIRMED,
            'visit_id' => $data['visit_id'] ?? null,
            'url' => $data['url'] ?? route('visits.index'),
        ];

        $this->send($recipient, NotificationLog::TYPE_VISIT_CONFIRMED, $title, $body, $notifData);
    }

    /**
     * Envoyer une notification de paiement
     */
    public function sendPaymentNotification(User $recipient, array $data): void
    {
        $title = $data['title'] ?? 'Notification de paiement';
        $body = $data['body'] ?? 'Un paiement a été effectué';

        $notifData = [
            'type' => NotificationLog::TYPE_PAYMENT,
            'payment_id' => $data['payment_id'] ?? null,
            'amount' => $data['amount'] ?? null,
            'url' => $data['url'] ?? route('payments.index'),
        ];

        $this->send($recipient, NotificationLog::TYPE_PAYMENT, $title, $body, $notifData);
    }

    /**
     * Envoyer une notification de sécurité
     */
    public function sendSecurityNotification(User $recipient, string $title, string $body, array $data = []): void
    {
        $notifData = array_merge([
            'type' => NotificationLog::TYPE_SECURITY,
        ], $data);

        $this->send($recipient, NotificationLog::TYPE_SECURITY, $title, $body, $notifData, true);
    }

    /**
     * Envoyer une notification système
     */
    public function sendSystemNotification(User $recipient, string $title, string $body, array $data = []): void
    {
        $notifData = array_merge([
            'type' => NotificationLog::TYPE_SYSTEM,
        ], $data);

        $this->send($recipient, NotificationLog::TYPE_SYSTEM, $title, $body, $notifData);
    }

    /**
     * Envoyer une notification via tous les canaux activés
     */
    protected function send(
        User $recipient,
        string $type,
        string $title,
        string $body,
        array $data = [],
        bool $forceSecurity = false,
    ): void {
        $preference = $this->getPreferences($recipient);
        $category = $this->getCategory($type);

        // Pour les notifications de sécurité, on force tous les canaux
        if ($forceSecurity) {
            $channels = [NotificationLog::CHANNEL_EMAIL, NotificationLog::CHANNEL_PUSH, NotificationLog::CHANNEL_SMS];
        } else {
            $channels = $preference->getEnabledChannels($category);
        }

        foreach ($channels as $channel) {
            $this->sendToChannel($recipient, $channel, $type, $title, $body, $data);
        }
    }

    /**
     * Envoyer via un canal spécifique
     */
    protected function sendToChannel(
        User $recipient,
        string $channel,
        string $type,
        string $title,
        string $body,
        array $data,
    ): void {
        // Créer le log
        $log = NotificationLog::log($recipient->id, $channel, $type, $title, $body, $data);

        try {
            match ($channel) {
                NotificationLog::CHANNEL_EMAIL => $this->sendEmail($recipient, $title, $body, $data),
                NotificationLog::CHANNEL_PUSH => $this->sendPush($recipient, $title, $body, $data),
                NotificationLog::CHANNEL_SMS => $this->sendSms($recipient, $body),
                default => null,
            };

            $log->markAsSent();
        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            Log::error('Notification failed', [
                'channel' => $channel,
                'user_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoyer un email
     */
    protected function sendEmail(User $recipient, string $title, string $body, array $data): void
    {
        Mail::send('emails.notification', [
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'actionUrl' => $data['url'] ?? null,
        ], function ($message) use ($recipient, $title) {
            $message->to($recipient->email)
                    ->subject($title);
        });
    }

    /**
     * Envoyer une notification push
     */
    protected function sendPush(User $recipient, string $title, string $body, array $data): void
    {
        $subscriptions = PushSubscription::forUser($recipient->id)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $validSubscriptions = $subscriptions->filter(
            fn (PushSubscription $subscription): bool => $this->isAllowedPushEndpoint($subscription->endpoint),
        );

        $invalidSubscriptionIds = $subscriptions
            ->reject(fn (PushSubscription $subscription): bool => $this->isAllowedPushEndpoint($subscription->endpoint))
            ->pluck('id')
            ->all();

        if ($invalidSubscriptionIds !== []) {
            PushSubscription::whereIn('id', $invalidSubscriptionIds)->delete();

            Log::warning('Invalid push subscriptions removed', [
                'user_id' => $recipient->id,
                'count' => count($invalidSubscriptionIds),
            ]);
        }

        if ($validSubscriptions->isEmpty()) {
            return;
        }

        // Configuration Web Push (VAPID)
        $auth = [
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => asset('images/icon-192.png'),
            'badge' => asset('images/badge.png'),
            'data' => $data,
            'actions' => [
                ['action' => 'open', 'title' => 'Ouvrir'],
                ['action' => 'dismiss', 'title' => 'Ignorer'],
            ],
        ]);

        foreach ($validSubscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
            ]);

            $webPush->queueNotification($subscription, $payload);
        }

        foreach ($webPush->flush() as $report) {
            if (! $report->isSuccess() && $report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $report->getEndpoint())->delete();
            }
        }
    }

    /**
     * Envoyer un SMS
     */
    protected function sendSms(User $recipient, string $body): void
    {
        if (!$recipient->phone) {
            return;
        }

        // Intégration avec un service SMS (Twilio, Orange SMS API, etc.)
        // Pour Côte d'Ivoire, on peut utiliser Orange SMS API ou autres

        // Exemple avec Twilio (à configurer)
        // $twilio = new \Twilio\Rest\Client(
        //     config('services.twilio.sid'),
        //     config('services.twilio.token')
        // );
        // $twilio->messages->create($recipient->phone, [
        //     'from' => config('services.twilio.from'),
        //     'body' => $body
        // ]);

        Log::info('SMS simulation only', [
            'to' => SensitiveData::maskPhone($recipient->phone),
            'message_length' => mb_strlen($body),
        ]);
    }

    public function isAllowedPushEndpoint(string $endpoint): bool
    {
        return $this->guard()->isSafe($endpoint, self::ALLOWED_PUSH_HOST_SUFFIXES);
    }

    protected function guard(): PublicUrlGuard
    {
        return $this->publicUrlGuard ?? app(PublicUrlGuard::class);
    }

    /**
     * Obtenir les préférences de l'utilisateur
     */
    protected function getPreferences(User $user): NotificationPreference
    {
        return NotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            NotificationPreference::getDefaults(),
        );
    }

    /**
     * Mapper le type de notification vers la catégorie de préférence
     */
    protected function getCategory(string $type): string
    {
        return match ($type) {
            NotificationLog::TYPE_MESSAGE => NotificationPreference::CATEGORY_MESSAGES,
            NotificationLog::TYPE_VISIT_REQUEST,
            NotificationLog::TYPE_VISIT_CONFIRMED => NotificationPreference::CATEGORY_VISITS,
            NotificationLog::TYPE_PAYMENT => NotificationPreference::CATEGORY_PAYMENTS,
            NotificationLog::TYPE_PROMOTION => NotificationPreference::CATEGORY_MARKETING,
            NotificationLog::TYPE_SECURITY => NotificationPreference::CATEGORY_SECURITY,
            default => NotificationPreference::CATEGORY_MESSAGES,
        };
    }

    /**
     * Tronquer le texte
     */
    protected function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length).'...';
    }

    /**
     * Souscrire aux notifications push
     */
    public function subscribePush(User $user, array $subscription): PushSubscription
    {
        return PushSubscription::updateOrCreateForUser($user->id, $subscription);
    }

    /**
     * Désinscrire des notifications push
     */
    public function unsubscribePush(User $user, string $endpoint): bool
    {
        return PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $endpoint)
            ->delete() > 0;
    }

    /**
     * Mettre à jour les préférences de notification
     */
    public function updatePreferences(User $user, array $preferences): NotificationPreference
    {
        $pref = $this->getPreferences($user);
        $pref->update($preferences);

        return $pref->fresh();
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(int $notificationId): void
    {
        NotificationLog::find($notificationId)?->markAsRead();
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(User $user): int
    {
        return NotificationLog::forUser($user->id)
            ->unread()
            ->update(['read_at' => now(), 'status' => NotificationLog::STATUS_READ]);
    }

    /**
     * Obtenir les notifications non lues
     */
    public function getUnreadCount(User $user): int
    {
        return NotificationLog::forUser($user->id)
            ->channel(NotificationLog::CHANNEL_DATABASE)
            ->unread()
            ->count();
    }

    /**
     * Obtenir les notifications récentes
     */
    public function getRecentNotifications(User $user, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return NotificationLog::forUser($user->id)
            ->channel(NotificationLog::CHANNEL_DATABASE)
            ->recent()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Envoyer une notification in-app (pour le centre de notifications)
     */
    public function sendInAppNotification(User $recipient, string $type, string $title, string $body, array $data = []): NotificationLog
    {
        return NotificationLog::create([
            'user_id' => $recipient->id,
            'channel' => NotificationLog::CHANNEL_DATABASE,
            'notification_type' => $type,
            'subject' => $title,
            'content' => $body,
            'data' => $data,
            'status' => NotificationLog::STATUS_DELIVERED,
            'sent_at' => now(),
        ]);
    }
}
