<?php

namespace App\Http\Controllers;

use App\Events\UserTyping;
use App\Models\AutoReply;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\Notification;
use App\Models\Residence;
use App\Models\SharedDocument;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ChatController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
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
            // Les propriétaires voient leurs propres résidences
            $residences = $user->residences()->approved()->get(['id', 'name', 'commune']);
        } else {
            // Les clients/admins voient toutes les résidences actives
            $residences = Residence::approved()->with('owner:id,name')->get(['id', 'name', 'commune', 'owner_id']);
        }

        return view('chat.index', compact('conversations', 'stats', 'archived', 'residences'));
    }

    /**
     * Afficher une conversation
     */
    public function show(Request $request, Conversation $conversation): View
    {
        $user = $request->user();

        // Vérifier l'accès
        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            abort(403);
        }

        // Charger les relations
        $conversation->load(['residence', 'user', 'owner', 'sharedDocuments']);
        $messages = $conversation->messages()
            ->with(['sender', 'template'])
            ->get();

        // Marquer les messages comme lus
        $this->chatService->markAsRead($conversation, $user);

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

        return view('chat.show', compact(
            'conversation',
            'messages',
            'templates',
            'quickReplies',
            'conversations',
        ));
    }

    /**
     * Envoyer un message
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        // Vérifier l'accès
        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'template_id' => 'nullable|exists:message_templates,id',
        ]);

        $message = $this->chatService->sendMessage(
            $conversation,
            $user,
            $validated['content'],
            $validated['template_id'] ?? null,
        );

        $message->load(['sender']);

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => view('chat.partials.message', compact('message'))->render(),
        ]);
    }

    /**
     * Envoyer une pièce jointe
     */
    public function sendAttachment(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'caption' => 'nullable|string|max:500',
        ]);

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
            'html' => view('chat.partials.message', compact('message'))->render(),
        ]);
    }

    /**
     * Modifier un message
     */
    public function editMessage(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();

        if ($message->sender_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        // On ne peut modifier que dans les 15 minutes
        if ($message->created_at->diffInMinutes(now()) > 15) {
            return response()->json(['error' => 'Délai de modification dépassé'], 422);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

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
     * Marquer les messages comme lus
     */
    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

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
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $conversation->archive();

        return response()->json(['success' => true]);
    }

    /**
     * Désarchiver une conversation
     */
    public function unarchive(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $conversation->unarchive();

        return response()->json(['success' => true]);
    }

    /**
     * Épingler une conversation
     */
    public function pin(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $conversation->pin();

        return response()->json(['success' => true]);
    }

    /**
     * Désépingler une conversation
     */
    public function unpin(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $conversation->unpin();

        return response()->json(['success' => true]);
    }

    /**
     * Mettre en sourdine
     */
    public function mute(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

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
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

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
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

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
            'html' => view('chat.partials.message', compact('message'))->render(),
        ]);
    }

    /**
     * Partager un document
     */
    public function shareDocument(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $validated = $request->validate([
            'document_id' => 'required|exists:shared_documents,id',
        ]);

        $document = SharedDocument::findOrFail($validated['document_id']);

        // Vérifier que l'utilisateur possède ce document
        if ($document->user_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $message = $this->chatService->sendSharedDocument($conversation, $user, $document);

        $message->load('sender');

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => view('chat.partials.message', compact('message'))->render(),
        ]);
    }

    /**
     * Obtenir les nouveaux messages (polling fallback)
     */
    public function getNewMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $validated = $request->validate([
            'after' => 'required|integer',
        ]);

        $messages = $conversation->messages()
            ->where('id', '>', $validated['after'])
            ->with(['sender'])
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'html' => $messages->map(fn ($m) => view('chat.partials.message', ['message' => $m])->render()),
        ]);
    }

    /**
     * Charger les messages avec pagination (scroll infini)
     */
    public function loadMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

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
            $messages = $messages->slice(1);
        }

        return response()->json([
            'messages' => $messages->map(function ($m) use ($user) {
                return [
                    'id' => $m->id,
                    'content' => $m->content,
                    'is_own' => $m->sender_id === $user->id,
                    'created_at' => $m->created_at->toISOString(),
                    'status' => $m->read_at ? 'read' : ($m->delivered_at ? 'delivered' : 'sent'),
                    'attachments' => $m->attachments,
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

        // Empêcher le propriétaire de discuter avec lui-même
        if ($residence->owner_id === $user->id && !$request->has('user_id')) {
            return back()->with('error', 'Vous ne pouvez pas vous envoyer un message.');
        }

        // Déterminer les rôles
        if ($residence->owner_id === $user->id && $request->has('user_id')) {
            $searchUserId = $request->user_id;
            $ownerId = $user->id;
        } else {
            $searchUserId = $user->id;
            $ownerId = $residence->owner_id;
        }

        // Chercher ou créer la conversation
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

        // Si un message initial est fourni
        if ($request->filled('message')) {
            $this->chatService->sendMessage($conversation, $user, $request->message);

            // Notifier l'autre participant
            $otherParticipant = $conversation->getOtherParticipant($user);
            Notification::send(
                $otherParticipant,
                'message',
                'Nouveau message',
                $user->name . ' vous a envoyé un message concernant ' . $residence->title,
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
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        broadcast(new UserTyping($conversation->id, $user))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Bloquer une conversation
     */
    public function block(Request $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Accès non autorisé'], 403);
            }
            abort(403);
        }

        $conversation->block();

        // Notifier l'autre utilisateur
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
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            abort(403);
        }

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

        // Vérifier l'accès à la conversation
        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            abort(403);
        }

        // Vérifier que le message a des pièces jointes
        $attachments = $message->attachments;
        if (!$attachments || !isset($attachments[$index])) {
            abort(404, 'Fichier non trouvé');
        }

        $attachment = $attachments[$index];
        $path = Storage::disk('private')->path($attachment['path']);

        if (!file_exists($path)) {
            return back()->with('error', 'Fichier introuvable');
        }

        $filename = $attachment['name'] ?? 'fichier';

        return response()->download($path, $filename);
    }
}
