<?php

namespace App\Http\Controllers;

use App\Events\UserTyping;
use App\Http\Requests\Chat\EditMessageRequest;
use App\Http\Requests\Chat\SendAttachmentRequest;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Models\AutoReply;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\MessageTemplate;
use App\Models\Notification;
use App\Models\Residence;
use App\Models\SharedDocument;
use App\Services\ChatService;
use App\Services\LinkPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ChatController extends Controller
{
    protected ChatService $chatService;
    protected LinkPreviewService $linkPreviewService;

    public function __construct(ChatService $chatService, LinkPreviewService $linkPreviewService)
    {
        $this->chatService = $chatService;
        $this->linkPreviewService = $linkPreviewService;
    }

    /**
     * Afficher la liste des conversations
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $archived = $request->boolean('archived');

        $conversations = $this->chatService->getUserConversations($user, $archived);
        $stats = $this->chatService->getStats($user);

        // Résidences disponibles pour démarrer une nouvelle conversation
        if ($user->isOwner()) {
            $residences = $user->residences()->approved()->get(['id', 'name', 'commune']);
        } else {
            $residences = Residence::approved()->with('owner:id,name')->get(['id', 'name', 'commune', 'owner_id']);
        }

        return view('chat.index', compact('conversations', 'stats', 'archived', 'residences'));
    }

    /**
     * Afficher une conversation avec pagination (50 derniers messages)
     */
    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $user = $request->user();

        // Charger les relations
        $conversation->load(['residence', 'user', 'owner', 'sharedDocuments']);

        // Charger les 50 derniers messages (au lieu de tout charger)
        $messages = $conversation->messages()
            ->with(['sender', 'template', 'reactions'])
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        $hasMoreMessages = $conversation->messages()->count() > 50;

        // Marquer les messages comme lus
        $this->chatService->markAsRead($conversation, $user);

        // Marquer les messages non-envoyés comme "délivrés"
        $conversation->messages()
            ->whereNull('delivered_at')
            ->where('sender_id', '!=', $user->id)
            ->update(['delivered_at' => now()]);

        // Obtenir les templates disponibles
        $templates = $this->chatService->getTemplatesForUser($user);

        // Obtenir les réponses rapides (manuelles) du propriétaire
        $quickReplies = collect();
        if ($user->id === $conversation->owner_id) {
            $quickReplies = AutoReply::where('user_id', $user->id)
                ->where('trigger_type', 'manual')
                ->where('is_active', true)
                ->where(function ($q) use ($conversation) {
                    $q->where('residence_id', $conversation->residence_id)
                      ->orWhereNull('residence_id');
                })
                ->orderBy('usage_count', 'desc')
                ->get();
        }

        // Autres conversations pour la sidebar
        $conversations = $this->chatService->getUserConversations($user);

        // "Dernière connexion" de l'autre participant
        $other = $conversation->getOtherParticipant($user);
        $otherLastSeen = $user->id === $conversation->user_id
            ? $conversation->owner_last_seen_at
            : $conversation->user_last_seen_at;

        return view('chat.show', compact(
            'conversation',
            'messages',
            'hasMoreMessages',
            'templates',
            'quickReplies',
            'conversations',
            'otherLastSeen',
        ));
    }

    /**
     * Envoyer un message (avec Form Request + reply_to + link preview)
     */
    public function sendMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $user = $request->user();
        $validated = $request->validated();

        $message = $this->chatService->sendMessage(
            $conversation,
            $user,
            $validated['content'],
            $validated['template_id'] ?? null,
            $validated['reply_to_id'] ?? null,
        );

        // Link preview (async-ish: on le fait après l'envoi)
        $url = $this->linkPreviewService->extractFirstUrl($validated['content']);
        if ($url) {
            $preview = $this->linkPreviewService->extract($url);
            if ($preview) {
                $message->update(['link_preview' => $preview]);
            }
        }

        $message->load(['sender', 'reactions']);

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => view('chat.partials.message', compact('message', 'conversation'))->render(),
        ]);
    }

    /**
     * Envoyer une pièce jointe (avec Form Request)
     */
    public function sendAttachment(SendAttachmentRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $user = $request->user();
        $validated = $request->validated();

        $message = $this->chatService->sendAttachment(
            $conversation,
            $user,
            $validated['file'],
            $validated['caption'] ?? null,
        );

        $message->load('sender');

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => view('chat.partials.message', compact('message', 'conversation'))->render(),
        ]);
    }

    /**
     * Modifier un message (auth + 15min dans EditMessageRequest)
     */
    public function editMessage(EditMessageRequest $request, Message $message): JsonResponse
    {
        $validated = $request->validated();
        $message = $this->chatService->editMessage($message, $validated['content']);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Supprimer un message
     */
    public function deleteMessage(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();

        if ($message->sender_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $this->chatService->deleteMessage($message);

        return response()->json(['success' => true]);
    }

    /**
     * Marquer les messages comme lus + delivered
     */
    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $user = $request->user();

        // Marquer delivered_at en même temps
        $conversation->messages()
            ->whereNull('delivered_at')
            ->where('sender_id', '!=', $user->id)
            ->update(['delivered_at' => now()]);

        $count = $this->chatService->markAsRead($conversation, $user);

        return response()->json([
            'success' => true,
            'read_count' => $count,
        ]);
    }

    /**
     * Archiver une conversation
     */
    public function archive(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('archive', $conversation);
        $conversation->archive();

        return response()->json(['success' => true]);
    }

    /**
     * Désarchiver une conversation
     */
    public function unarchive(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('archive', $conversation);
        $conversation->unarchive();

        return response()->json(['success' => true]);
    }

    /**
     * Épingler une conversation
     */
    public function pin(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('pin', $conversation);
        $conversation->pin();

        return response()->json(['success' => true]);
    }

    /**
     * Désépingler une conversation
     */
    public function unpin(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('pin', $conversation);
        $conversation->unpin();

        return response()->json(['success' => true]);
    }

    /**
     * Mettre en sourdine
     */
    public function mute(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('mute', $conversation);

        $validated = $request->validate([
            'until' => 'nullable|date|after:now',
        ]);

        $until = !empty($validated['until']) ? \Carbon\Carbon::parse($validated['until']) : null;
        $conversation->mute($until);

        return response()->json(['success' => true]);
    }

    /**
     * Réactiver les notifications
     */
    public function unmute(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('mute', $conversation);
        $conversation->unmute();

        return response()->json(['success' => true]);
    }

    /**
     * Rechercher dans les messages
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:100',
        ]);

        $messages = $this->chatService->searchMessages(
            $request->user(),
            $validated['query'],
        );

        return response()->json([
            'success' => true,
            'results' => $messages,
        ]);
    }

    /**
     * Obtenir les templates
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $category = $request->get('category');
        $templates = $this->chatService->getTemplatesForUser($request->user(), $category);

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Utiliser un template
     */
    public function useTemplate(Request $request, Conversation $conversation, MessageTemplate $template): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $user = $request->user();
        $variables = $request->get('variables', []);

        $message = $this->chatService->useTemplate(
            $template,
            $conversation,
            $user,
            $variables,
        );

        $message->load(['sender', 'template']);

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => view('chat.partials.message', compact('message', 'conversation'))->render(),
        ]);
    }

    /**
     * Partager un document
     */
    public function shareDocument(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $user = $request->user();

        $validated = $request->validate([
            'document_id' => 'required|exists:shared_documents,id',
        ]);

        $document = SharedDocument::findOrFail($validated['document_id']);

        if ($document->user_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $message = $this->chatService->sendSharedDocument($conversation, $user, $document);
        $message->load('sender');

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => view('chat.partials.message', compact('message', 'conversation'))->render(),
        ]);
    }

    /**
     * Obtenir les nouveaux messages (polling fallback) + marquer delivered
     */
    public function getNewMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $user = $request->user();

        $validated = $request->validate([
            'after' => 'required|integer',
        ]);

        $messages = $conversation->messages()
            ->where('id', '>', $validated['after'])
            ->with(['sender'])
            ->get();

        // Marquer les nouveaux messages comme délivrés
        if ($messages->isNotEmpty()) {
            $conversation->messages()
                ->whereIn('id', $messages->pluck('id'))
                ->whereNull('delivered_at')
                ->where('sender_id', '!=', $user->id)
                ->update(['delivered_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'html' => $messages->map(fn ($m) => view('chat.partials.message', ['message' => $m, 'conversation' => $conversation])->render()),
        ]);
    }

    /**
     * Charger les messages avec pagination (scroll infini vers le haut)
     */
    public function loadMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $user = $request->user();
        $beforeId = $request->query('before');
        $limit = 50;

        $query = $conversation->messages()->with('sender');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->orderBy('id', 'desc')
            ->limit($limit + 1)
            ->get()
            ->reverse()
            ->values();

        $hasMore = $messages->count() > $limit;
        if ($hasMore) {
            $messages = $messages->slice(1)->values();
        }

        return response()->json([
            'messages' => $messages->map(function ($m) use ($user) {
                return [
                    'id' => $m->id,
                    'content' => $m->content,
                    'sender_id' => $m->sender_id,
                    'is_own' => $m->sender_id === $user->id,
                    'type' => $m->type,
                    'attachments' => $m->attachments,
                    'metadata' => $m->metadata,
                    'is_auto_reply' => $m->is_auto_reply,
                    'created_at' => $m->created_at->toISOString(),
                    'read_at' => $m->read_at?->toISOString(),
                    'delivered_at' => $m->delivered_at?->toISOString(),
                    'status' => $m->read_at ? 'read' : ($m->delivered_at ? 'delivered' : 'sent'),
                    'sender_name' => $m->sender?->name,
                    'sender_avatar' => $m->sender?->getAvatarUrl(),
                ];
            }),
            'has_more' => $hasMore,
        ]);
    }

    /**
     * Démarrer ou rejoindre une conversation
     */
    public function start(Request $request): RedirectResponse
    {
        $request->validate([
            'residence_id' => 'required|exists:residences,id',
            'message' => 'nullable|string|max:5000',
        ]);

        $user = $request->user();
        $residence = Residence::findOrFail($request->residence_id);

        if ($residence->owner_id === $user->id && !$request->has('user_id')) {
            return back()->with('error', 'Vous ne pouvez pas vous envoyer un message.');
        }

        if ($residence->owner_id === $user->id && $request->has('user_id')) {
            $searchUserId = $request->user_id;
            $ownerId = $user->id;
        } else {
            $searchUserId = $user->id;
            $ownerId = $residence->owner_id;
        }

        $conversation = Conversation::firstOrCreate(
            [
                'residence_id' => $residence->id,
                'user_id' => $searchUserId,
            ],
            [
                'owner_id' => $ownerId,
                'last_message_at' => now(),
            ],
        );

        if ($request->filled('message')) {
            $this->chatService->sendMessage($conversation, $user, $request->message);

            $otherParticipant = $conversation->getOtherParticipant($user);
            Notification::send(
                $otherParticipant,
                'message',
                'Nouveau message',
                $user->name.' vous a envoyé un message concernant '.$residence->title,
                route('chat.show', $conversation),
                ['residence_id' => $residence->id],
            );
        }

        return redirect()->route('chat.show', $conversation);
    }

    /**
     * Indicateur de frappe en temps réel
     */
    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $user = $request->user();
        broadcast(new UserTyping($conversation->id, $user))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Bloquer une conversation
     */
    public function block(Request $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $this->authorize('block', $conversation);

        $user = $request->user();
        $conversation->block();

        $otherParticipant = $conversation->getOtherParticipant($user);
        Notification::send(
            $otherParticipant,
            'system',
            'Conversation bloquée',
            'Une conversation a été bloquée. Vous ne pouvez plus envoyer de messages.',
            route('chat.index'),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur bloqué. Il ne pourra plus vous envoyer de messages.',
            ]);
        }

        return redirect()->route('chat.index')
            ->with('success', 'Utilisateur bloqué.');
    }

    /**
     * Supprimer une conversation
     */
    public function destroy(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('delete', $conversation);

        $conversation->delete();

        return redirect()->route('chat.index')
            ->with('success', 'Conversation supprimée.');
    }

    /**
     * Télécharger une pièce jointe d'un message
     */
    public function downloadAttachment(Request $request, Message $message, int $index): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $conversation = $message->conversation;

        $this->authorize('view', $conversation);

        $attachments = $message->attachments;
        if (!$attachments || !isset($attachments[$index])) {
            abort(404, 'Fichier non trouvé');
        }

        $attachment = $attachments[$index];
        $path = Storage::disk('private')->path($attachment['path']);

        if (!file_exists($path)) {
            return back()->with('error', 'Fichier introuvable');
        }

        return response()->download($path, $attachment['name'] ?? 'fichier');
    }

    // =========================================================================
    // MESSENGER-LIKE FEATURES
    // =========================================================================

    /**
     * Ajouter / retirer une réaction emoji sur un message
     */
    public function toggleReaction(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();
        $conversation = $message->conversation;
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $emoji = $validated['emoji'];

        if (!in_array($emoji, MessageReaction::ALLOWED_EMOJIS)) {
            return response()->json(['error' => 'Emoji non autorisé'], 422);
        }

        // Toggle: si déjà réagi avec le même emoji, on supprime
        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            $existing->delete();
            $action = 'removed';
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'emoji' => $emoji,
            ]);
            $action = 'added';
        }

        $reactions = $message->fresh()->getGroupedReactions();

        return response()->json([
            'success' => true,
            'action' => $action,
            'reactions' => $reactions,
        ]);
    }

    /**
     * Changer le thème couleur d'une conversation
     */
    public function changeTheme(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'color' => 'required|string|in:'.implode(',', array_keys(Conversation::THEME_COLORS)),
        ]);

        $conversation->update(['theme_color' => $validated['color']]);

        return response()->json([
            'success' => true,
            'theme' => Conversation::THEME_COLORS[$validated['color']],
        ]);
    }

    /**
     * Envoyer un message vocal (audio blob)
     */
    public function sendVoice(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $request->validate([
            'audio' => 'required|file|mimes:webm,ogg,mp3,m4a,wav|max:5120', // 5Mo max
        ]);

        $user = $request->user();
        $file = $request->file('audio');
        $duration = $request->input('duration', 0); // durée en secondes

        $path = $file->store('voice_messages/'.$conversation->id, 'private');

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'content' => '', // Pas de texte
            'type' => Message::TYPE_VOICE,
            'attachments' => [[
                'path' => $path,
                'name' => 'Voice message',
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'duration' => (int) $duration,
            ]],
            'metadata' => ['duration' => (int) $duration],
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Incrémenter unread counter
        if ($user->id === $conversation->user_id) {
            $conversation->update(['unread_owner_count' => DB::raw('unread_owner_count + 1')]);
        } else {
            $conversation->update(['unread_user_count' => DB::raw('unread_user_count + 1')]);
        }

        $message->load('sender');
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => view('chat.partials.message', compact('message', 'conversation'))->render(),
        ]);
    }

    /**
     * Envoyer un GIF (via URL Tenor/Giphy)
     */
    public function sendGif(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $validated = $request->validate([
            'gif_url' => 'required|url|max:500',
            'preview_url' => 'nullable|url|max:500',
            'width' => 'nullable|integer',
            'height' => 'nullable|integer',
        ]);

        $user = $request->user();

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'content' => '',
            'type' => Message::TYPE_GIF,
            'metadata' => [
                'gif_url' => $validated['gif_url'],
                'preview_url' => $validated['preview_url'] ?? $validated['gif_url'],
                'width' => $validated['width'] ?? null,
                'height' => $validated['height'] ?? null,
            ],
        ]);

        $conversation->update(['last_message_at' => now()]);

        if ($user->id === $conversation->user_id) {
            $conversation->update(['unread_owner_count' => DB::raw('unread_owner_count + 1')]);
        } else {
            $conversation->update(['unread_user_count' => DB::raw('unread_user_count + 1')]);
        }

        $message->load('sender');
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => view('chat.partials.message', compact('message', 'conversation'))->render(),
        ]);
    }

    /**
     * Rechercher les GIFs via Tenor API v2
     */
    public function searchGifs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:50',
        ]);

        $apiKey = config('services.tenor.key');
        $limit = 20;

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(5)->get('https://tenor.googleapis.com/v2/search', [
                'q' => $validated['q'],
                'key' => $apiKey,
                'client_key' => 'rezi_app',
                'limit' => $limit,
                'media_filter' => 'gif,tinygif',
                'locale' => 'fr_FR',
            ]);

            if ($response->successful()) {
                $results = collect($response->json('results', []))->map(function ($gif) {
                    return [
                        'id' => $gif['id'],
                        'url' => $gif['media_formats']['gif']['url'] ?? '',
                        'preview' => $gif['media_formats']['tinygif']['url'] ?? ($gif['media_formats']['gif']['url'] ?? ''),
                        'width' => $gif['media_formats']['gif']['dims'][0] ?? 200,
                        'height' => $gif['media_formats']['gif']['dims'][1] ?? 200,
                    ];
                });

                return response()->json(['success' => true, 'gifs' => $results]);
            }
        } catch (\Exception $e) {
            // fallback silencieux
        }

        return response()->json(['success' => false, 'gifs' => []]);
    }

    /**
     * Rechercher dans les messages d'une conversation spécifique
     */
    public function searchInConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $search = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $validated['q']);

        $messages = $conversation->messages()
            ->where('content', 'like', "%{$search}%")
            ->where('type', 'text')
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get(['id', 'content', 'sender_id', 'created_at'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'content' => $m->content,
                'is_own' => $m->sender_id === $request->user()->id,
                'date' => $m->created_at->diffForHumans(),
            ]);

        return response()->json(['success' => true, 'results' => $messages]);
    }

    /**
     * Extraire un aperçu de lien (endpoint AJAX)
     */
    public function linkPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
        ]);

        $preview = $this->linkPreviewService->extract($validated['url']);

        return response()->json([
            'success' => $preview !== null,
            'preview' => $preview,
        ]);
    }

    /**
     * Stream audio vocal
     */
    public function streamImage(Request $request, Message $message, int $index = 0): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $conversation = $message->conversation;
        $this->authorize('view', $conversation);

        if ($message->type !== Message::TYPE_IMAGE || empty($message->attachments)) {
            abort(404);
        }

        $attachments = $message->attachments;
        if (!isset($attachments[$index])) {
            abort(404);
        }

        $attachment = $attachments[$index];
        $path = Storage::disk('private')->path($attachment['path']);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => $attachment['mime'] ?? 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function streamVoice(Request $request, Message $message): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $user = $request->user();
        $conversation = $message->conversation;
        $this->authorize('view', $conversation);

        if ($message->type !== Message::TYPE_VOICE || empty($message->attachments)) {
            abort(404);
        }

        $attachment = $message->attachments[0];
        $path = Storage::disk('private')->path($attachment['path']);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => $attachment['mime'] ?? 'audio/webm',
        ]);
    }
}
