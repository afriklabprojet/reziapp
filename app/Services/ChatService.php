<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Models\AutoReply;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\Residence;
use App\Models\SharedDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Obtenir ou créer une conversation
     */
    public function getOrCreateConversation(
        User $user,
        User $owner,
        Residence $residence,
    ): Conversation {
        return Conversation::firstOrCreate(
            [
                'residence_id' => $residence->id,
                'user_id' => $user->id,
                'owner_id' => $owner->id,
            ],
            [
                'status' => Conversation::STATUS_ACTIVE,
                'last_message_at' => now(),
            ],
        );
    }

    /**
     * Obtenir les conversations d'un utilisateur
     */
    public function getUserConversations(User $user, bool $archived = false): Collection
    {
        $query = Conversation::with(['residence', 'user', 'owner', 'lastMessage'])
            ->forUser($user)
            ->ordered();

        if ($archived) {
            $query->archived();
        } else {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Envoyer un message texte
     */
    public function sendMessage(
        Conversation $conversation,
        User $sender,
        string $content,
        ?int $templateId = null,
        ?int $replyToId = null,
    ): Message {
        // Sauvegarder le message en transaction (DB uniquement)
        $message = DB::transaction(function () use ($conversation, $sender, $content, $templateId, $replyToId) {
            // Préparer les metadata avec reply_to si présent
            $metadata = [];
            if ($replyToId) {
                $replyMessage = Message::find($replyToId);
                if ($replyMessage) {
                    $metadata['reply_to_id'] = $replyToId;
                    $metadata['reply_to_content'] = Str::limit($replyMessage->content, 100);
                    $metadata['reply_to_sender'] = $replyMessage->sender?->name;
                }
            }

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'content' => $content,
                'type' => Message::TYPE_TEXT,
                'template_id' => $templateId,
                'metadata' => !empty($metadata) ? $metadata : null,
            ]);

            // Mettre à jour le timestamp + compteur non-lu
            $updateData = ['last_message_at' => now()];
            if ($sender->id === $conversation->user_id) {
                $updateData['unread_owner_count'] = DB::raw('unread_owner_count + 1');
            } else {
                $updateData['unread_user_count'] = DB::raw('unread_user_count + 1');
            }
            $conversation->update($updateData);

            // Si un template est utilisé, incrémenter son compteur
            if ($templateId) {
                MessageTemplate::find($templateId)?->incrementUsage();
            }

            return $message;
        });

        // Side-effects hors transaction (ne rollback pas le message si ça échoue)
        try {
            $this->checkAutoReply($conversation, $sender);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Auto-reply failed', ['error' => $e->getMessage()]);
        }

        try {
            $recipient = $conversation->getOtherParticipant($sender);
            $this->notifyNewMessage($message, $recipient);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Message notification failed', ['error' => $e->getMessage()]);
        }

        try {
            $this->broadcastMessage($message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Message broadcast failed', ['error' => $e->getMessage()]);
        }

        return $message;
    }

    /**
     * Envoyer un message avec pièce jointe
     */
    public function sendAttachment(
        Conversation $conversation,
        User $sender,
        UploadedFile $file,
        ?string $caption = null,
    ): Message {
        // Sauvegarder le message en transaction (DB uniquement)
        $message = DB::transaction(function () use ($conversation, $sender, $file, $caption) {
            // Déterminer le type de message
            $type = $this->getFileType($file);

            // Stocker le fichier
            $path = $file->store("conversations/{$conversation->id}", 'private');

            $attachment = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'type' => $type,
            ];

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'content' => $caption ?? '',
                'type' => $type,
                'attachments' => [$attachment],
            ]);

            // Mettre à jour le timestamp + compteur non-lu
            $updateData = ['last_message_at' => now()];
            if ($sender->id === $conversation->user_id) {
                $updateData['unread_owner_count'] = DB::raw('unread_owner_count + 1');
            } else {
                $updateData['unread_user_count'] = DB::raw('unread_user_count + 1');
            }
            $conversation->update($updateData);

            return $message;
        });

        // Side-effects hors transaction
        try {
            $recipient = $conversation->getOtherParticipant($sender);
            $this->notifyNewMessage($message, $recipient);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Attachment notification failed', ['error' => $e->getMessage()]);
        }

        try {
            $this->broadcastMessage($message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Attachment broadcast failed', ['error' => $e->getMessage()]);
        }

        return $message;
    }

    /**
     * Envoyer un document partagé
     */
    public function sendSharedDocument(
        Conversation $conversation,
        User $sender,
        SharedDocument $document,
    ): Message {
        // Sauvegarder le message en transaction (DB uniquement)
        $message = DB::transaction(function () use ($conversation, $sender, $document) {
            // Lier le document à la conversation
            $document->update(['conversation_id' => $conversation->id]);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'content' => "📄 Document partagé : {$document->name}",
                'type' => Message::TYPE_DOCUMENT,
                'metadata' => [
                    'document_id' => $document->id,
                    'document_type' => $document->type,
                ],
            ]);

            // Mettre à jour le timestamp + compteur non-lu
            $updateData = ['last_message_at' => now()];
            if ($sender->id === $conversation->user_id) {
                $updateData['unread_owner_count'] = DB::raw('unread_owner_count + 1');
            } else {
                $updateData['unread_user_count'] = DB::raw('unread_user_count + 1');
            }
            $conversation->update($updateData);

            return $message;
        });

        // Side-effects hors transaction
        try {
            $recipient = $conversation->getOtherParticipant($sender);
            $this->notifyNewMessage($message, $recipient);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Document notification failed', ['error' => $e->getMessage()]);
        }

        try {
            $this->broadcastMessage($message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Document broadcast failed', ['error' => $e->getMessage()]);
        }

        return $message;
    }

    /**
     * Envoyer un message système
     */
    public function sendSystemMessage(
        Conversation $conversation,
        string $content,
        array $metadata = [],
    ): Message {
        return Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => null,
            'content' => $content,
            'type' => Message::TYPE_SYSTEM,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Modifier un message
     */
    public function editMessage(Message $message, string $newContent): Message
    {
        $message->edit($newContent);
        $message->refresh();

        try {
            $this->broadcastMessageEdited($message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Message edit broadcast failed', ['error' => $e->getMessage()]);
        }

        return $message;
    }

    /**
     * Supprimer un message
     */
    public function deleteMessage(Message $message): bool
    {
        // Supprimer les pièces jointes si présentes
        if ($message->attachments) {
            foreach ($message->attachments as $attachment) {
                Storage::disk('private')->delete($attachment['path']);
            }
        }

        $message->delete();
        $this->broadcastMessageDeleted($message);

        return true;
    }

    /**
     * Marquer les messages comme lus
     */
    public function markAsRead(Conversation $conversation, User $user): int
    {
        $count = $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->update(['read_at' => now()]);

        if ($count > 0) {
            // Remettre à zéro le compteur non-lu pour cet utilisateur
            if ($user->id === $conversation->user_id) {
                $conversation->update(['unread_user_count' => 0, 'user_last_seen_at' => now()]);
            } else {
                $conversation->update(['unread_owner_count' => 0, 'owner_last_seen_at' => now()]);
            }

            $this->broadcastMessagesRead($conversation, $user);
        }

        return $count;
    }

    /**
     * Rechercher dans les messages
     */
    public function searchMessages(User $user, string $query): Collection
    {
        // Échapper les caractères spéciaux du LIKE pour éviter l'injection
        $escapedQuery = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $query);

        return Message::whereHas('conversation', function ($q) use ($user) {
            $q->forUser($user);
        })
        ->where('content', 'like', "%{$escapedQuery}%")
        ->with(['conversation.residence', 'sender'])
        ->orderByDesc('created_at')
        ->limit(50)
        ->get();
    }

    /**
     * Vérifier et envoyer une réponse automatique
     */
    protected function checkAutoReply(Conversation $conversation, User $sender): void
    {
        // L'auto-reply s'applique uniquement quand c'est le client qui envoie
        if ($sender->id === $conversation->owner_id) {
            return;
        }

        // Éviter de spammer — max 1 auto-reply toutes les 24h par conversation
        $recentAutoReply = $conversation->messages()
            ->where('type', Message::TYPE_AUTO_REPLY)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($recentAutoReply) {
            return;
        }

        $owner = $conversation->owner;
        $lastMessage = $conversation->messages()
            ->where('sender_id', $sender->id)
            ->latest()
            ->first();

        $messageContent = $lastMessage?->content ?? '';

        // Vérifier si c'est le premier message du client dans cette conversation
        $isFirstMessage = $conversation->messages()
            ->where('sender_id', $sender->id)
            ->count() <= 1;

        $context = [
            'is_first_message' => $isFirstMessage,
            'residence_id' => $conversation->residence_id,
        ];

        // Chercher les auto-replies du propriétaire (spécifiques à la résidence d'abord, puis globales)
        $autoReplies = AutoReply::where('user_id', $owner->id)
            ->where('is_active', true)
            ->where(function ($q) use ($conversation) {
                $q->where('residence_id', $conversation->residence_id)
                  ->orWhereNull('residence_id');
            })
            ->orderByRaw('residence_id IS NULL ASC') // Spécifique d'abord
            ->get();

        // Trouver la première réponse dont la condition est remplie
        $matchedReply = $autoReplies->first(function ($autoReply) use ($messageContent, $context) {
            return $autoReply->shouldTrigger($messageContent, $context);
        });

        if (!$matchedReply) {
            return;
        }

        // Préparer les variables pour le message
        $variables = [
            'guest_name' => $sender->name ?? $sender->first_name ?? 'Cher client',
            'residence_name' => $conversation->residence?->name ?? 'notre résidence',
            'owner_name' => $owner->name ?? 'Le propriétaire',
            'price' => $conversation->residence?->price_per_day ? number_format($conversation->residence->price_per_day, 0, ',', ' ') . ' F/nuit' : '',
            'address' => $conversation->residence?->address ?? '',
            'phone' => $owner->phone ?? '',
            'checkin_time' => '14h00',
            'checkout_time' => '11h00',
        ];

        $formattedMessage = $matchedReply->formatMessage($variables);

        // Envoyer avec délai ou immédiatement
        if ($matchedReply->delay_minutes > 0) {
            // Dispatch un job avec délai
            dispatch(function () use ($conversation, $owner, $formattedMessage, $matchedReply) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $owner->id,
                    'content' => $formattedMessage,
                    'type' => Message::TYPE_AUTO_REPLY,
                    'metadata' => ['auto_reply_id' => $matchedReply->id],
                ]);
                $matchedReply->markAsUsed();
            })->delay(now()->addMinutes($matchedReply->delay_minutes));
        } else {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $owner->id,
                'content' => $formattedMessage,
                'type' => Message::TYPE_AUTO_REPLY,
                'metadata' => ['auto_reply_id' => $matchedReply->id],
            ]);
            $matchedReply->markAsUsed();
        }
    }

    /**
     * Utiliser un template de message
     */
    public function useTemplate(
        MessageTemplate $template,
        Conversation $conversation,
        User $sender,
        array $variables = [],
    ): Message {
        // Ajouter les variables contextuelles
        $variables = array_merge([
            'residence_name' => $conversation->residence->name ?? '',
            'user_name' => $conversation->user->name ?? '',
            'owner_name' => $conversation->owner->name ?? '',
        ], $variables);

        $content = $template->generateContent($variables);

        return $this->sendMessage(
            $conversation,
            $sender,
            $content,
            $template->id,
        );
    }

    /**
     * Obtenir les templates disponibles pour un utilisateur
     */
    public function getTemplatesForUser(User $user, ?string $category = null): Collection
    {
        $query = MessageTemplate::forUser($user);

        if ($category) {
            $query->category($category);
        }

        return $query->orderBy('usage_count', 'desc')->get();
    }

    /**
     * Traduction automatique (optionnel)
     */
    public function translateMessage(Message $message, string $targetLanguage): ?string
    {
        // Intégration avec un service de traduction (Google Translate, DeepL, etc.)
        // Pour l'instant, on retourne null (fonctionnalité optionnelle)
        return null;
    }

    /**
     * Déterminer le type de fichier
     */
    protected function getFileType(UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        if (Str::startsWith($mime, 'image/')) {
            return Message::TYPE_IMAGE;
        }

        return Message::TYPE_FILE;
    }

    /**
     * Notifier le destinataire d'un nouveau message
     */
    protected function notifyNewMessage(Message $message, User $recipient): void
    {
        $this->notificationService->sendMessageNotification($message, $recipient);
    }

    /**
     * Broadcast du message pour le temps réel
     */
    protected function broadcastMessage(Message $message): void
    {
        // L'événement sera broadcasté via Laravel Broadcasting
        event(new MessageSent($message));
    }

    /**
     * Broadcast de l'édition d'un message
     */
    protected function broadcastMessageEdited(Message $message): void
    {
        event(new \App\Events\MessageEdited($message));
    }

    /**
     * Broadcast de la suppression d'un message
     */
    protected function broadcastMessageDeleted(Message $message): void
    {
        event(new \App\Events\MessageDeleted($message));
    }

    /**
     * Broadcast des messages lus
     */
    protected function broadcastMessagesRead(Conversation $conversation, User $user): void
    {
        event(new MessagesRead($conversation, $user));
    }

    /**
     * Obtenir les statistiques de messagerie pour un utilisateur
     */
    public function getStats(User $user): array
    {
        $conversations = Conversation::forUser($user);

        return [
            'total_conversations' => $conversations->count(),
            'active_conversations' => (clone $conversations)->active()->count(),
            'unread_messages' => Message::whereHas('conversation', function ($q) use ($user) {
                $q->forUser($user);
            })->whereNull('read_at')->where('sender_id', '!=', $user->id)->count(),
            'messages_sent' => Message::where('sender_id', $user->id)->count(),
            'messages_received' => Message::whereHas('conversation', function ($q) use ($user) {
                $q->forUser($user);
            })->where('sender_id', '!=', $user->id)->count(),
        ];
    }
}
