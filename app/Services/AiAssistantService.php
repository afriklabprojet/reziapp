<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'assistance IA pour la génération de contenu.
 *
 * Supporte deux providers :
 *  1. Google Gemini (gratuit, recommandé) — GEMINI_API_KEY
 *  2. OpenAI (payant, fallback)           — OPENAI_API_KEY
 *
 * Utilisé pour :
 *  - Générer des descriptions d'annonces immobilières
 *  - Suggérer des clauses pour les contrats de bail
 *  - Améliorer le contenu existant
 *
 * Protections :
 *  - Rate limit : 10 appels LLM par utilisateur par heure
 *  - Sanitisation des inputs utilisateur (anti prompt-injection)
 *  - Circuit breaker : 3 échecs API en 5 min => pause 5 min
 *  - Clé cache isolée par userId pour éviter les collisions cross-users
 */
class AiAssistantService
{
    private const AI_RATE_LIMIT_MAX     = 10;
    private const AI_RATE_LIMIT_TTL_SEC = 3600; // 1 heure
    private const CIRCUIT_BREAKER_MAX   = 3;
    private const CIRCUIT_BREAKER_TTL   = 300;  // 5 minutes
    private const INPUT_MAX_LENGTH      = 2000;

    private string $provider; // 'gemini' | 'openai' | 'none'
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        // Priorité : Gemini > OpenAI
        $geminiKey = config('services.gemini.api_key', '');
        $openaiKey = config('services.openai.api_key', '');

        if (! empty($geminiKey)) {
            $this->provider = 'gemini';
            $this->apiKey   = $geminiKey;
            $this->model    = config('services.gemini.model', 'gemini-2.5-flash');
        } elseif (! empty($openaiKey)) {
            $this->provider = 'openai';
            $this->apiKey   = $openaiKey;
            $this->model    = config('services.openai.model', 'gpt-4o-mini');
        } else {
            $this->provider = 'none';
            $this->apiKey   = '';
            $this->model    = '';
        }
    }

    /**
     * Vérifie si le service IA est configuré.
     */
    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    // ====================================================================
    // ANNONCES / RÉSIDENCES
    // ====================================================================

    /**
     * Générer une description attractive pour une annonce immobilière.
     */
    public function generateListingDescription(array $propertyData, ?int $userId = null): ?string
    {
        $prompt = $this->buildListingPrompt($propertyData);

        return $this->complete($prompt, 'listing_desc', maxTokens: 500, userId: $userId);
    }

    /**
     * Générer un titre accrocheur pour une annonce.
     */
    public function generateListingTitle(array $propertyData, ?int $userId = null): ?string
    {
        $type         = $this->sanitizeUserInput($propertyData['type'] ?? 'logement');
        $commune      = $this->sanitizeUserInput($propertyData['commune'] ?? 'Abidjan');
        $quartier     = $this->sanitizeUserInput($propertyData['quartier'] ?? '');
        $bedrooms     = $this->sanitizeUserInput((string) ($propertyData['bedrooms'] ?? ''));
        $typeLocation = $propertyData['type_location'] ?? 'residence_meublee';

        $typeLabel = match ($type) {
            'studio'    => 'Studio',
            'apartment' => 'Appartement',
            'house'     => 'Maison',
            'villa'     => 'Villa',
            'duplex'    => 'Duplex',
            default     => 'Logement',
        };

        $locationLabel = match ($typeLocation) {
            'apartment'         => 'location longue durée',
            'residence_meublee' => 'meublé(e)',
            'hotel'             => 'hôtelière',
            default             => '',
        };

        $prompt = <<<PROMPT
Tu es un expert en immobilier à Abidjan, Côte d'Ivoire. Génère UN titre d'annonce accrocheur et professionnel.

Informations du bien :
- Type : {$typeLabel} {$locationLabel}
- Localisation : {$commune}{$quartier}
- Chambres : {$bedrooms}

Règles :
- Maximum 80 caractères
- En français
- Pas de guillemets dans la réponse
- Mets en avant le côté attractif et la localisation
- Pas de prix dans le titre
- Pas d'emojis
- Un seul titre, sans explication

Titre :
PROMPT;

        return $this->complete($prompt, 'listing_title', maxTokens: 60, userId: $userId);
    }

    /**
     * Améliorer une description existante.
     */
    public function improveListingDescription(string $currentDescription, array $propertyData, ?int $userId = null): ?string
    {
        $sanitizedDescription = $this->sanitizeUserInput($currentDescription);
        $type                 = $this->sanitizeUserInput($propertyData['type'] ?? 'logement');
        $commune              = $this->sanitizeUserInput($propertyData['commune'] ?? '');
        $quartier             = $this->sanitizeUserInput($propertyData['quartier'] ?? '');

        $prompt = <<<PROMPT
Tu es un rédacteur immobilier expert à Abidjan. Améliore cette description d'annonce.

Description actuelle :
"{$sanitizedDescription}"

Contexte : {$type} à {$commune} {$quartier}

Règles :
- Garde le même sens et les informations factuelles
- Améliore le style, la structure et l'attractivité
- Maximum 500 mots
- En français
- Ajoute des paragraphes bien structurés
- Pas de titre, juste la description améliorée
- Pas de guillemets autour de la réponse

Description améliorée :
PROMPT;

        return $this->complete($prompt, 'listing_improve', maxTokens: 800, userId: $userId);
    }

    // ====================================================================
    // CONTRATS DE BAIL
    // ====================================================================

    /**
     * Générer des clauses spéciales pour un contrat de bail.
     */
    public function generateContractClauses(array $contractData, ?int $userId = null): ?string
    {
        $type        = $contractData['lease_type'] ?? 'monthly';
        $rent        = (int) ($contractData['monthly_rent'] ?? 0);
        $deposit     = (int) ($contractData['deposit_amount'] ?? 0);
        $residence   = $this->sanitizeUserInput($contractData['residence_name'] ?? 'la résidence');
        $commune     = $this->sanitizeUserInput($contractData['commune'] ?? 'Abidjan');
        $services    = $contractData['included_services'] ?? [];

        $typeLabel = match ($type) {
            'short_term' => 'location courte durée (meublé)',
            'monthly'    => 'location mensuelle',
            'fixed_term' => 'location durée déterminée',
            default      => 'location',
        };

        $servicesText = count($services) > 0
            ? 'Services inclus : '.implode(', ', array_map([$this, 'sanitizeUserInput'], $services))
            : 'Aucun service inclus spécifié';

        $prompt = <<<PROMPT
Tu es un juriste spécialisé en droit immobilier ivoirien (OHADA). Rédige des clauses particulières pour un contrat de bail.

Informations du contrat :
- Type : {$typeLabel}
- Montant de location : {$rent} FCFA/mois
- Dépôt de garantie : {$deposit} FCFA
- Bien : {$residence} à {$commune}
- {$servicesText}

Rédige 4 à 6 clauses particulières pertinentes couvrant :
1. Conditions d'utilisation des lieux
2. Entretien et réparations
3. Règles de voisinage et nuisances
4. Conditions spécifiques au type de bail

Règles :
- En français juridique clair et accessible
- Applicable en Côte d'Ivoire (zone OHADA)
- Chaque clause numérotée (1., 2., etc.)
- Pas de titres de section, directement les clauses
- Maximum 400 mots
- Pas de guillemets autour de la réponse

Clauses :
PROMPT;

        return $this->complete($prompt, 'contract_clauses', maxTokens: 600, userId: $userId);
    }

    /**
     * Suggérer des services inclus pour un contrat.
     */
    public function suggestContractServices(array $contractData, ?int $userId = null): ?array
    {
        $type    = $contractData['lease_type'] ?? 'monthly';
        $commune = $this->sanitizeUserInput($contractData['commune'] ?? 'Abidjan');
        $rent    = (int) ($contractData['monthly_rent'] ?? 0);

        $prompt = <<<PROMPT
Tu es un expert en gestion immobilière à Abidjan. Pour un contrat de bail ({$type}) avec un montant mensuel de location de {$rent} FCFA/mois à {$commune}, suggère les services qui devraient être inclus dans la location.

Retourne UNIQUEMENT une liste JSON de services, sans explication.
Exemple : ["Électricité", "Eau", "Wifi", "Gardiennage"]

Maximum 8 services pertinents pour le type de bien et le standing (basé sur le montant mensuel).

Liste JSON :
PROMPT;

        $result = $this->complete($prompt, 'contract_services', maxTokens: 100, userId: $userId);

        if ($result) {
            // Extraire le JSON de la réponse
            if (preg_match('/\[.*\]/s', $result, $matches)) {
                $services = json_decode($matches[0], true);
                if (is_array($services)) {
                    return $services;
                }
            }
        }

        return null;
    }

    // ====================================================================
    // API CALL
    // ====================================================================

    /**
     * Appeler l'API IA (Gemini ou OpenAI selon la configuration).
     *
     * @param  int|null  $userId  Identifiant utilisateur pour le rate limit et l'isolation du cache.
     */
    private function complete(
        string $prompt,
        string $cachePrefix = '',
        int $maxTokens = 500,
        ?int $userId = null,
    ): ?string {
        if (! $this->isAvailable()) {
            return null;
        }

        // Circuit breaker : si l'API LLM a accumulé trop d'échecs, on suspend temporairement
        if ($this->isCircuitOpen()) {
            Log::warning('AI Assistant: circuit breaker ouvert, appel LLM suspendu', [
                'provider' => $this->provider,
            ]);

            return null;
        }

        // Rate limit par utilisateur : 10 appels par heure
        if ($userId !== null && ! $this->checkRateLimit($userId)) {
            return null;
        }

        // Clé cache isolée par userId pour éviter les collisions entre utilisateurs
        $userSegment = $userId !== null ? "_{$userId}" : '';
        $cacheKey    = $cachePrefix.$userSegment.'_'.md5($prompt);

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            $content = match ($this->provider) {
                'gemini' => $this->callGemini($prompt, $maxTokens),
                'openai' => $this->callOpenAI($prompt, $maxTokens),
                default  => null,
            };

            if ($content) {
                $content = trim($content);
                Cache::put($cacheKey, $content, now()->addMinutes(5));

                return $content;
            }

            // Réponse vide mais sans exception : on ne comptabilise pas comme échec API
        } catch (\Throwable $e) {
            $this->recordFailure();
            Log::error('AI Assistant: Exception', [
                'provider' => $this->provider,
                'message'  => $e->getMessage(),
            ]);
        }

        return null;
    }

    // ====================================================================
    // RATE LIMIT
    // ====================================================================

    /**
     * Vérifie et consomme un jeton de rate limit pour l'utilisateur.
     * Retourne false si la limite est atteinte.
     */
    private function checkRateLimit(int $userId): bool
    {
        $key     = "ai:rate_limit:{$userId}";
        $current = Cache::increment($key);

        if ($current === 1) {
            Cache::expire($key, self::AI_RATE_LIMIT_TTL_SEC);
        }

        if ($current > self::AI_RATE_LIMIT_MAX) {
            Log::warning('AI Assistant: rate limit dépassé', ['user_id' => $userId]);

            return false;
        }

        return true;
    }

    // ====================================================================
    // CIRCUIT BREAKER
    // ====================================================================

    /**
     * Indique si le circuit breaker est ouvert (API considérée down).
     */
    private function isCircuitOpen(): bool
    {
        return Cache::get("ai:circuit_breaker:{$this->provider}", 0) >= self::CIRCUIT_BREAKER_MAX;
    }

    /**
     * Enregistre un échec API et ouvre le circuit breaker si le seuil est atteint.
     */
    private function recordFailure(): void
    {
        $key     = "ai:circuit_breaker:{$this->provider}";
        $current = Cache::increment($key);

        if ($current === 1) {
            Cache::expire($key, self::CIRCUIT_BREAKER_TTL);
        }
    }

    // ====================================================================
    // SANITISATION
    // ====================================================================

    /**
     * Nettoie une chaîne fournie par l'utilisateur avant injection dans un prompt.
     *
     * Protège contre la prompt injection en :
     *  - tronquant à INPUT_MAX_LENGTH caractères
     *  - supprimant les séquences d'injection connues
     *  - échappant les backticks et les triple-guillemets
     */
    private function sanitizeUserInput(string $input): string
    {
        // Troncature
        $sanitized = mb_substr($input, 0, self::INPUT_MAX_LENGTH);

        // Séquences d'injection connues
        $injectionPatterns = [
            '/---+/',
            '/####+/',
            '/SYSTEM\s*:/i',
            '/IGNORE\s+PREVIOUS/i',
            '/\n\nHuman\s*:/i',
            '/\n\nAssistant\s*:/i',
            '/\[INST\]/i',
            '/\[\/INST\]/i',
        ];

        $sanitized = preg_replace($injectionPatterns, ' ', $sanitized) ?? $sanitized;

        // Caractères dangereux dans les prompts (backtick, triple-quote)
        $sanitized = str_replace(['`', '"""', "'''"], ["'", '"', '"'], $sanitized);

        return trim($sanitized);
    }

    // ====================================================================
    // CHATBOT CONVERSATION
    // ====================================================================

    /**
     * Conversation multi-tours avec l'IA (chatbot locataire 24/7).
     *
     * @param  array  $messages  Historique [{role: 'user'|'assistant', content: '...'}]
     * @param  array  $context   Contexte optionnel (résidence courante, commune, budget…)
     * @param  int|null  $userId  Identifiant utilisateur pour le rate limit
     */
    public function chat(array $messages, array $context = [], ?int $userId = null): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        if ($this->isCircuitOpen()) {
            Log::warning('AI Chatbot: circuit breaker ouvert', ['provider' => $this->provider]);

            return null;
        }

        if ($userId !== null && ! $this->checkRateLimit($userId)) {
            return null;
        }

        $systemPrompt = $this->buildChatbotSystemPrompt($context);

        try {
            $reply = match ($this->provider) {
                'gemini' => $this->callGeminiChat($systemPrompt, $messages),
                'openai' => $this->callOpenAIChat($systemPrompt, $messages),
                default  => null,
            };

            return $reply ? trim($reply) : null;
        } catch (\Throwable $e) {
            $this->recordFailure();
            Log::error('AI Chatbot: Exception', [
                'provider' => $this->provider,
                'error'    => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildChatbotSystemPrompt(array $context): string
    {
        $commune   = $this->sanitizeUserInput($context['commune'] ?? '');
        $budget    = $this->sanitizeUserInput((string) ($context['budget'] ?? ''));
        $residence = $this->sanitizeUserInput($context['residence'] ?? '');

        $contextLines = '';
        if ($commune) {
            $contextLines .= "\n- Commune d'intérêt : {$commune}";
        }
        if ($budget) {
            $contextLines .= "\n- Budget indicatif : {$budget} FCFA/mois";
        }
        if ($residence) {
            $contextLines .= "\n- Résidence consultée : {$residence}";
        }

        return <<<SYSTEM
Tu es l'assistant IA de REZI, plateforme de location de résidences meublées à Abidjan, Côte d'Ivoire.
Tu aides les locataires potentiels 24h/24, 7j/7.

Ton rôle :
- Répondre aux questions sur les résidences disponibles, les quartiers d'Abidjan, les prix du marché
- Aider à comprendre le processus de réservation REZI (contact → visite → dossier → contrat)
- Orienter vers le bon type de logement selon les besoins (durée, budget, quartier)
- Expliquer les documents nécessaires pour louer
- Rassurer sur les garanties REZI (paiements sécurisés, résidences vérifiées)
- Si tu ne sais pas quelque chose, recommander de contacter l'équipe REZI

Règles absolues :
- Toujours répondre en français
- Réponses courtes et utiles (3-5 phrases max sauf si on te demande des détails)
- Ne jamais inventer de prix ou de disponibilités spécifiques
- Ne jamais donner d'informations personnelles sur d'autres utilisateurs
- Si une question sort du domaine immobilier, recentrer poliment{$contextLines}

Tu parles au nom de REZI. Sois chaleureux, professionnel et utile.
SYSTEM;
    }

    private function callGeminiChat(string $systemPrompt, array $messages): ?string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        // Convertir l'historique au format Gemini
        $contents = [];
        foreach ($messages as $msg) {
            $role       = $msg['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role'  => $role,
                'parts' => [['text' => $msg['content']]],
            ];
        }

        // Prepend system prompt dans le premier message user
        if (! empty($contents) && $contents[0]['role'] === 'user') {
            $contents[0]['parts'][0]['text'] = $systemPrompt."\n\n".$contents[0]['parts'][0]['text'];
        }

        /** @var Response $response */
        $response = Http::timeout(30)->post($url, [
            'contents'         => $contents,
            'generationConfig' => [
                'maxOutputTokens' => 400,
                'temperature'     => 0.8,
                'thinkingConfig'  => [
                    'thinkingBudget' => 0,
                ],
            ],
        ]);

        if ($response->successful()) {
            return $response->json('candidates.0.content.parts.0.text');
        }

        $this->recordFailure();
        Log::warning('AI Chatbot (Gemini): API call failed', [
            'status' => $response->status(),
            'body'   => substr($response->body(), 0, 300),
        ]);

        return null;
    }

    private function callOpenAIChat(string $systemPrompt, array $messages): ?string
    {
        $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');

        $chatMessages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($messages as $msg) {
            $chatMessages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }

        /** @var Response $response */
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type'  => 'application/json',
        ])
        ->timeout(30)
        ->post("{$baseUrl}/chat/completions", [
            'model'       => $this->model,
            'messages'    => $chatMessages,
            'max_tokens'  => 400,
            'temperature' => 0.8,
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }

        $this->recordFailure();
        Log::warning('AI Chatbot (OpenAI): API call failed', [
            'status' => $response->status(),
            'body'   => substr($response->body(), 0, 300),
        ]);

        return null;
    }

    /**
     * Appeler l'API Google Gemini.
     */
    private function callGemini(string $prompt, int $maxTokens): ?string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        /** @var Response $response */
        $response = Http::timeout(30)->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => "Tu es un assistant expert en immobilier à Abidjan, Côte d'Ivoire. Tu réponds toujours en français. Tu es concis et professionnel.\n\n".$prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
                'temperature'     => 0.7,
                // Désactive le "thinking mode" de Gemini 2.5 Flash pour
                // éviter qu'il consomme tous les tokens en réflexion interne.
                'thinkingConfig'  => [
                    'thinkingBudget' => 0,
                ],
            ],
        ]);

        if ($response->successful()) {
            $text = $response->json('candidates.0.content.parts.0.text');
            if ($text) {
                return $text;
            }
        }

        $this->recordFailure();
        Log::warning('AI Assistant (Gemini): API call failed', [
            'status' => $response->status(),
            'body'   => substr($response->body(), 0, 500),
        ]);

        return null;
    }

    /**
     * Appeler l'API OpenAI.
     */
    private function callOpenAI(string $prompt, int $maxTokens): ?string
    {
        $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');

        /** @var Response $response */
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type'  => 'application/json',
        ])
        ->timeout(30)
        ->post("{$baseUrl}/chat/completions", [
            'model'       => $this->model,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'Tu es un assistant expert en immobilier à Abidjan, Côte d\'Ivoire. Tu réponds toujours en français. Tu es concis et professionnel.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens'  => $maxTokens,
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');
            if ($content) {
                return $content;
            }
        }

        $this->recordFailure();
        Log::warning('AI Assistant (OpenAI): API call failed', [
            'status' => $response->status(),
            'body'   => substr($response->body(), 0, 500),
        ]);

        return null;
    }

    // ====================================================================
    // PROMPT BUILDERS
    // ====================================================================

    private function buildListingPrompt(array $data): string
    {
        $type         = $this->sanitizeUserInput($data['type'] ?? 'logement');
        $typeLocation = $data['type_location'] ?? 'residence_meublee';
        $commune      = $this->sanitizeUserInput($data['commune'] ?? '');
        $quartier     = $this->sanitizeUserInput($data['quartier'] ?? '');
        $bedrooms     = $this->sanitizeUserInput((string) ($data['bedrooms'] ?? ''));
        $bathrooms    = $this->sanitizeUserInput((string) ($data['bathrooms'] ?? ''));
        $surface      = $this->sanitizeUserInput((string) ($data['surface_area'] ?? ''));
        $price        = (int) ($data['price'] ?? 0);
        $amenities    = $data['amenities'] ?? [];
        $name         = $this->sanitizeUserInput($data['name'] ?? '');

        $typeLabel = match ($type) {
            'studio'    => 'Studio',
            'apartment' => 'Appartement',
            'house'     => 'Maison',
            'villa'     => 'Villa',
            'duplex'    => 'Duplex',
            default     => 'Logement',
        };

        $locationLabel = match ($typeLocation) {
            'apartment'         => 'location longue durée',
            'residence_meublee' => 'résidence meublée',
            'hotel'             => 'hébergement hôtelier',
            default             => 'location',
        };

        $amenitiesText = count($amenities) > 0
            ? 'Équipements : '.implode(', ', array_map([$this, 'sanitizeUserInput'], $amenities))
            : '';

        return <<<PROMPT
Tu es un rédacteur professionnel spécialisé dans les annonces immobilières à Abidjan, Côte d'Ivoire.

Génère une description attractive et détaillée pour cette annonce :

Informations du bien :
- Titre : {$name}
- Type : {$typeLabel} ({$locationLabel})
- Localisation : {$commune} {$quartier}
- Chambres : {$bedrooms}
- Salles de bain : {$bathrooms}
- Surface : {$surface} m²
- Prix : {$price} FCFA
{$amenitiesText}

Règles de rédaction :
- En français, style professionnel et engageant
- 150 à 300 mots
- Structuré en 3-4 paragraphes courts
- Mettre en avant la localisation (quartier d'Abidjan, proximité commodités)
- Décrire l'ambiance et le confort du bien
- Mentionner les équipements s'il y en a
- Terminer par un call-to-action subtil
- Pas de titre, juste la description
- Pas de guillemets autour de la réponse
- Pas de prix dans la description

Description :
PROMPT;
    }
}
