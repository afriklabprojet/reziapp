<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Vérification du webhook par Facebook/Meta
     */
    public function verify(Request $request)
    {
        $verifyToken = config('services.whatsapp.verify_token');

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WhatsApp webhook verified');

            return response($challenge, 200);
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Vérifier la signature HMAC-SHA256 envoyée par Meta.
     *
     * Meta envoie le header X-Hub-Signature-256: sha256=<hex>
     * Calculé avec HMAC-SHA256(app_secret, raw_body).
     */
    private function verifySignature(Request $request): bool
    {
        $secret = config('services.whatsapp.webhook_secret');

        // Si aucun secret n'est configuré, on bloque par défaut (fail-secure)
        if (empty($secret)) {
            Log::channel('security')->critical('WhatsApp webhook: WHATSAPP_WEBHOOK_SECRET non configure', [
                'ip' => $request->ip(),
                'environment' => config('app.env'),
            ]);

            return false;
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Recevoir les événements webhook
     */
    public function handle(Request $request)
    {
        // Vérification HMAC obligatoire avant tout traitement
        if (! $this->verifySignature($request)) {
            Log::warning('WhatsApp webhook: signature invalide ou absente', [
                'ip' => $request->ip(),
                'signature' => $request->header('X-Hub-Signature-256'),
            ]);

            return response('Unauthorized', 401);
        }

        $payload = $request->all();

        Log::info('WhatsApp webhook received', ['payload' => $payload]);

        try {
            // Structure du webhook Meta
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];

                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];

                    // Traiter les statuts de messages
                    if (isset($value['statuses'])) {
                        $this->handleStatuses($value['statuses']);
                    }

                    // Traiter les messages entrants
                    if (isset($value['messages'])) {
                        $this->handleIncomingMessages($value['messages'], $value['contacts'] ?? []);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', ['error' => $e->getMessage()]);
        }

        // Toujours retourner 200 pour éviter les retry
        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Traiter les mises à jour de statut des messages
     */
    protected function handleStatuses(array $statuses): void
    {
        foreach ($statuses as $status) {
            $messageId = $status['id'] ?? null;
            $statusValue = $status['status'] ?? null;
            $timestamp = $status['timestamp'] ?? null;

            if (!$messageId || !$statusValue) {
                continue;
            }

            $message = WhatsappMessage::where('whatsapp_message_id', $messageId)->first();

            if (!$message) {
                continue;
            }

            $dateTime = $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp) : now();

            switch ($statusValue) {
                case 'sent':
                    $message->update([
                        'status' => WhatsappMessage::STATUS_SENT,
                        'sent_at' => $message->sent_at ?? $dateTime,
                    ]);
                    break;

                case 'delivered':
                    $message->update([
                        'status' => WhatsappMessage::STATUS_DELIVERED,
                        'delivered_at' => $dateTime,
                    ]);
                    break;

                case 'read':
                    $message->update([
                        'status' => WhatsappMessage::STATUS_READ,
                        'read_at' => $dateTime,
                    ]);
                    break;

                case 'failed':
                    $errors = $status['errors'] ?? [];
                    $message->update([
                        'status' => WhatsappMessage::STATUS_FAILED,
                        'failed_reason' => json_encode($errors),
                    ]);
                    break;

                default:
                    Log::info('WhatsApp message status ignored', [
                        'message_id' => $messageId,
                        'status' => $statusValue,
                    ]);
                    break;
            }

            Log::info("WhatsApp message {$messageId} status updated to {$statusValue}");
        }
    }

    /**
     * Traiter les messages entrants
     */
    protected function handleIncomingMessages(array $messages, array $contacts): void
    {
        foreach ($messages as $msg) {
            $from = $msg['from'] ?? null;
            $type = $msg['type'] ?? 'text';
            $timestamp = $msg['timestamp'] ?? null;
            $messageId = $msg['id'] ?? null;

            if (!$from) {
                continue;
            }

            // Trouver le contact
            $contactInfo = collect($contacts)->firstWhere('wa_id', $from);
            $name = $contactInfo['profile']['name'] ?? null;

            // Extraire le contenu
            $content = $this->extractMessageContent($msg);

            // Enregistrer le message entrant
            $incomingMessage = WhatsappMessage::create([
                'phone_number' => $from,
                'message_type' => 'incoming_'.$type,
                'message_content' => $content,
                'whatsapp_message_id' => $messageId,
                'status' => 'received',
                'sent_at' => $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp) : now(),
                'metadata' => [
                    'contact_name' => $name,
                    'raw' => $msg,
                ],
            ]);

            // Trouver l'utilisateur par numéro
            $user = User::where('phone', 'LIKE', '%'.substr($from, -8))->first();

            if ($user) {
                $incomingMessage->update(['user_id' => $user->id]);

                // Router vers une conversation ou répondre automatiquement
                $this->routeIncomingMessage($user, $content, $from, $name);
            } else {
                // Réponse automatique pour les non-inscrits
                $this->sendWelcomeMessage($from, $name);
            }

            Log::info("WhatsApp incoming message from {$from}", ['content' => $content]);
        }
    }

    /**
     * Extraire le contenu du message selon son type
     */
    protected function extractMessageContent(array $msg): string
    {
        $type = $msg['type'] ?? 'text';

        return match ($type) {
            'text' => $msg['text']['body'] ?? '',
            'image' => '[Image: '.($msg['image']['caption'] ?? 'Sans légende').']',
            'document' => '[Document: '.($msg['document']['filename'] ?? 'Fichier').']',
            'audio' => '[Message vocal]',
            'video' => '[Vidéo: '.($msg['video']['caption'] ?? 'Sans légende').']',
            'location' => '[Localisation: '.($msg['location']['name'] ?? 'Position').']',
            'contacts' => '[Contact partagé]',
            'sticker' => '[Sticker]',
            'interactive' => $this->extractInteractiveResponse($msg['interactive'] ?? []),
            'button' => $msg['button']['text'] ?? '[Bouton]',
            default => "[Message de type: {$type}]",
        };
    }

    /**
     * Extraire la réponse interactive (boutons, listes)
     */
    protected function extractInteractiveResponse(array $interactive): string
    {
        $type = $interactive['type'] ?? '';

        if ($type === 'button_reply') {
            return $interactive['button_reply']['title'] ?? '';
        }

        if ($type === 'list_reply') {
            return $interactive['list_reply']['title'] ?? '';
        }

        return '[Réponse interactive]';
    }

    /**
     * Router le message entrant vers la conversation appropriée
     */
    protected function routeIncomingMessage(User $user, string $content, string $phone, ?string $name): void
    {
        // Vérifier les mots-clés pour réponses automatiques
        $keywords = $this->detectKeywords($content);

        if ($keywords) {
            $this->handleKeywordResponse($phone, $keywords, $user);

            return;
        }

        // Sinon, créer une notification pour le support
        $this->notifySupportTeam($user, $content, $phone);
    }

    /**
     * Détecter les mots-clés dans le message
     */
    protected function detectKeywords(string $content): ?array
    {
        $content = strtolower($content);

        $keywordMappings = [
            'aide' => ['type' => 'help'],
            'help' => ['type' => 'help'],
            'allo' => ['type' => 'help'],
            'réservation' => ['type' => 'booking_info'],
            'reservation' => ['type' => 'booking_info'],
            'annuler' => ['type' => 'cancel_info'],
            'prix' => ['type' => 'pricing'],
            'tarif' => ['type' => 'pricing'],
            'contact' => ['type' => 'contact'],
            'bonjour' => ['type' => 'greeting'],
            'bonsoir' => ['type' => 'greeting'],
            'salut' => ['type' => 'greeting'],
        ];

        foreach ($keywordMappings as $keyword => $data) {
            if (str_contains($content, $keyword)) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Envoyer une réponse basée sur le mot-clé détecté
     */
    protected function handleKeywordResponse(string $phone, array $keywords, User $user): void
    {
        $type = $keywords['type'];

        $responses = [
            'greeting' => "Bonjour ! 👋\n\nBienvenue sur Rezi Studio Meublé Faya, votre partenaire pour trouver la résidence meublée idéale à Abidjan.\n\nComment puis-je vous aider ?\n• Tapez 'aide' pour voir les options\n• Tapez 'réservation' pour vos réservations\n• Tapez 'contact' pour nous joindre",

            'help' => "🏠 *Rezi Studio Meublé Faya - Aide*\n\nVoici ce que je peux faire pour vous :\n\n1️⃣ Chercher une résidence\n🔗 https://reziapp.ci/residences\n\n2️⃣ Voir mes réservations\n🔗 https://reziapp.ci/bookings\n\n3️⃣ Contacter le support\n📞 +225 XX XX XX XX XX\n\n4️⃣ Questions fréquentes\n🔗 https://reziapp.ci/faq",

            'booking_info' => "📋 *Vos réservations*\n\nPour gérer vos réservations :\n🔗 https://reziapp.ci/bookings\n\nOu contactez notre équipe pour assistance.",

            'cancel_info' => "❌ *Annulation*\n\nPour annuler une réservation :\n1. Connectez-vous sur https://reziapp.ci\n2. Allez dans 'Mes réservations'\n3. Sélectionnez la réservation\n4. Cliquez sur 'Annuler'\n\n⚠️ Les conditions d'annulation varient selon la résidence.",

            'pricing' => "💰 *Tarifs*\n\nLes prix varient selon :\n• Le type de résidence\n• La localisation\n• La durée du séjour\n\nConsultez nos offres :\n🔗 https://reziapp.ci/residences",

            'contact' => "📞 *Contact Rezi Studio Meublé Faya*\n\n📱 WhatsApp: +225 XX XX XX XX XX\n📧 Email: support@reziapp.ci\n🌐 Site: https://reziapp.ci\n\nNos horaires :\nLun-Ven: 8h-18h\nSam: 9h-13h",
        ];

        $message = $responses[$type] ?? $responses['help'];

        $this->whatsAppService->sendText($phone, $message, $user->id);
    }

    /**
     * Envoyer un message de bienvenue aux non-inscrits
     */
    protected function sendWelcomeMessage(string $phone, ?string $name): void
    {
        $greeting = $name ? "Bonjour {$name} ! 👋" : 'Bonjour ! 👋';

        $message = "{$greeting}\n\nMerci de contacter Rezi Studio Meublé Faya, votre plateforme de location de résidences meublées à Abidjan.\n\n🏠 Créez votre compte gratuitement :\nhttps://reziapp.ci/register\n\n✨ Avantages :\n• Accès à +500 résidences\n• Réservation en ligne\n• Paiement sécurisé\n• Support 7j/7\n\nÀ bientôt sur Rezi Studio Meublé Faya !";

        $this->whatsAppService->sendText($phone, $message);
    }

    /**
     * Notifier l'équipe support d'un message entrant
     */
    protected function notifySupportTeam(User $user, string $content, string $phone): void
    {
        // Créer une notification pour l'admin
        Log::info("Support notification: User {$user->id} sent message via WhatsApp", [
            'phone' => $phone,
            'content' => $content,
        ]);

        // Optionnel: Envoyer au canal Slack/Discord de support
        // Ou créer un ticket dans le système admin
    }
}
