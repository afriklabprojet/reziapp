<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Message;
use App\Services\MessageTranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TranslateMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 30;

    public function __construct(public int $messageId) {}

    public function handle(MessageTranslationService $service): void
    {
        $message = Message::find($this->messageId);
        if (!$message || !empty($message->translated_content)) {
            return;
        }

        // Détermine la locale du destinataire
        $recipient = $message->conversation?->participants()
            ->where('users.id', '!=', $message->sender_id)
            ->first();

        $targetLocale = $recipient?->locale ?? config('app.fallback_locale', 'fr');

        try {
            $service->translateMessage($message, $targetLocale);
        } catch (\Throwable $e) {
            Log::warning('TranslateMessageJob failed', ['msg' => $message->id, 'err' => $e->getMessage()]);
        }
    }
}
