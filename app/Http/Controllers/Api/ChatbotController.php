<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ChatbotController extends Controller
{
    public function __construct(
        private readonly AiAssistantService $ai,
    ) {
    }

    /**
     * POST /api/v1/chatbot/message
     *
     * Body JSON:
     * {
     *   "messages": [{"role": "user", "content": "..."}],   // historique complet
     *   "context": {                                          // optionnel
     *     "commune": "Cocody",
     *     "budget": 150000,
     *     "residence": "Villa Douce Cocody"
     *   }
     * }
     */
    public function message(Request $request): JsonResponse
    {
        // Rate limiting par IP : 30 messages/minute
        $key = 'chatbot:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json([
                'reply'   => 'Vous envoyez trop de messages. Veuillez patienter une minute.',
                'limited' => true,
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'messages'              => ['required', 'array', 'min:1', 'max:20'],
            'messages.*.role'       => ['required', 'string', 'in:user,assistant'],
            'messages.*.content'    => ['required', 'string', 'max:1000'],
            'context'               => ['sometimes', 'array'],
            'context.commune'       => ['sometimes', 'string', 'max:100'],
            'context.budget'        => ['sometimes', 'nullable', 'integer', 'min:0'],
            'context.residence'     => ['sometimes', 'string', 'max:200'],
        ]);

        if (! $this->ai->isAvailable()) {
            return response()->json([
                'reply' => 'Je suis temporairement indisponible. Contactez-nous sur WhatsApp ou par email à contact@rezi.ci.',
                'error' => true,
            ]);
        }

        $reply = $this->ai->chat(
            $validated['messages'],
            $validated['context'] ?? [],
        );

        if (! $reply) {
            return response()->json([
                'reply' => "Je n'ai pas pu traiter votre demande. Réessayez ou contactez notre équipe à contact@rezi.ci.",
                'error' => true,
            ]);
        }

        return response()->json([
            'reply'   => $reply,
            'error'   => false,
            'limited' => false,
        ]);
    }

    /**
     * GET /api/v1/chatbot/status
     * Vérifie si le chatbot IA est disponible.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'available' => $this->ai->isAvailable(),
        ]);
    }
}
