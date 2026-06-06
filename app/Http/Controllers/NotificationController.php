<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NotificationController extends Controller
{
    protected ?NotificationService $notificationService = null;

    protected function getNotificationService(): NotificationService
    {
        if (!$this->notificationService) {
            $this->notificationService = app(NotificationService::class);
        }

        return $this->notificationService;
    }

    /**
     * Liste des notifications
     */
    public function index()
    {
        $notifications = Auth::user()
            ->notifications()
            ->paginate(20);

        // Notifications modernes (NotificationLog)
        $notificationLogs = NotificationLog::forUser(Auth::id())
            ->recent(30)
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'logs_page');

        $unreadCount = NotificationLog::forUser(Auth::id())->unread()->count();

        return view('notifications.index', compact('notifications', 'notificationLogs', 'unreadCount'));
    }

    /**
     * Afficher les préférences de notification
     */
    public function preferences(): View
    {
        $user = Auth::user();
        $preferences = NotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            NotificationPreference::getDefaults(),
        );

        $categories = NotificationPreference::getCategories();
        $channels = NotificationPreference::getChannels();
        $pushSubscriptions = PushSubscription::forUser($user->id)->get();

        return view('notifications.preferences', compact('preferences', 'categories', 'channels', 'pushSubscriptions'));
    }

    /**
     * Mettre à jour les préférences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'messages_email' => 'boolean',
            'messages_push' => 'boolean',
            'messages_sms' => 'boolean',
            'visits_email' => 'boolean',
            'visits_push' => 'boolean',
            'visits_sms' => 'boolean',
            'payments_email' => 'boolean',
            'payments_push' => 'boolean',
            'payments_sms' => 'boolean',
            'marketing_email' => 'boolean',
            'marketing_push' => 'boolean',
            'marketing_sms' => 'boolean',
            'security_email' => 'boolean',
            'security_push' => 'boolean',
            'security_sms' => 'boolean',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
            'timezone' => 'nullable|string|timezone',
        ]);

        $preferences = $this->getNotificationService()->updatePreferences(
            Auth::user(),
            $validated,
        );

        return response()->json([
            'success' => true,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->markAsRead();

        if ($notification->action_url) {
            return redirect($notification->action_url);
        }

        return back();
    }

    /**
     * Marquer un log de notification comme lu
     */
    public function markLogAsRead(Request $request, NotificationLog $notificationLog): JsonResponse
    {
        if ($notificationLog->user_id !== Auth::id()) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $notificationLog->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        // Aussi marquer les NotificationLogs
        NotificationLog::forUser(Auth::id())
            ->unread()
            ->update(['read_at' => now(), 'status' => NotificationLog::STATUS_READ]);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->delete();

        return back()->with('success', 'Notification supprimée.');
    }

    /**
     * S'abonner aux notifications push
     */
    public function subscribePush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        if (! $this->getNotificationService()->isAllowedPushEndpoint($validated['endpoint'])) {
            throw ValidationException::withMessages([
                'endpoint' => 'Endpoint push non autorisé.',
            ]);
        }

        $subscription = $this->getNotificationService()->subscribePush(
            Auth::user(),
            $validated,
        );

        return response()->json([
            'success' => true,
            'subscription' => $subscription,
        ]);
    }

    /**
     * Se désabonner des notifications push
     */
    public function unsubscribePush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|url',
        ]);

        $result = $this->getNotificationService()->unsubscribePush(
            Auth::user(),
            $validated['endpoint'],
        );

        return response()->json(['success' => $result]);
    }

    /**
     * Obtenir la clé publique VAPID
     */
    public function getVapidKey(): JsonResponse
    {
        return response()->json([
            'publicKey' => config('services.webpush.public_key'),
        ]);
    }

    /**
     * API: Obtenir les notifications non lues (pour le header)
     */
    public function unreadCount()
    {
        $oldCount = Auth::user()->unreadNotifications()->count();
        $newCount = NotificationLog::forUser(Auth::id())->unread()->count();

        return response()->json([
            'count' => $oldCount + $newCount,
            'legacy_count' => $oldCount,
            'log_count' => $newCount,
        ]);
    }

    /**
     * API: Dernières notifications
     */
    public function latest()
    {
        $notifications = Auth::user()
            ->notifications()
            ->take(5)
            ->get();

        $notificationLogs = NotificationLog::forUser(Auth::id())
            ->recent(7)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'notification_logs' => $notificationLogs,
            'unread_count' => Auth::user()->unreadNotifications()->count() +
                              NotificationLog::forUser(Auth::id())->unread()->count(),
        ]);
    }

    /**
     * Tester l'envoi d'une notification (dev only)
     */
    public function test(Request $request): JsonResponse
    {
        if (!app()->isLocal()) {
            return response()->json(['error' => 'Non disponible en production'], 403);
        }

        $this->getNotificationService()->sendSystemNotification(
            Auth::user(),
            'Test de notification',
            'Ceci est un test de notification ReziApp.',
            ['test' => true],
        );

        return response()->json(['success' => true]);
    }

    /**
     * Tester l'envoi d'une notification push
     */
    public function testPush(Request $request): JsonResponse
    {
        $this->getNotificationService()->sendSystemNotification(
            Auth::user(),
            'Test Push ReziApp 🔔',
            'Les notifications push fonctionnent correctement !',
            ['url' => route('notifications.index'), 'test' => true],
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification push envoyée',
        ]);
    }

    /**
     * Envoyer une notification broadcast à tous les utilisateurs (admin only)
     */
    public function broadcast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'body' => 'required|string|max:500',
        ]);

        $users = \App\Models\User::all();
        $count = 0;

        foreach ($users as $user) {
            $this->getNotificationService()->sendSystemNotification(
                $user,
                $validated['title'],
                $validated['body'],
                ['broadcast' => true],
            );
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "Notification envoyée à {$count} utilisateurs",
            'count' => $count,
        ]);
    }
}
