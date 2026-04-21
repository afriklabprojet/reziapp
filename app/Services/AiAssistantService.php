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
 */
class AiAssistantService
{
    private string $provider; // 'gemini' | 'openai'
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
    public function generateListingDescription(array $propertyData): ?string
    {
        $prompt = $this->buildListingPrompt($propertyData);

        return $this->complete($prompt, 'listing_desc');
    }

    /**
     * Générer un titre accrocheur pour une annonce.
     */
    public function generateListingTitle(array $propertyData): ?string
    {
        $type     = $propertyData['type'] ?? 'logement';
        $commune  = $propertyData['commune'] ?? 'Abidjan';
        $quartier = $propertyData['quartier'] ?? '';
        $bedrooms = $propertyData['bedrooms'] ?? '';
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

        return $this->complete($prompt, 'listing_title', maxTokens: 60);
    }

    /**
     * Améliorer une description existante.
     */
    public function improveListingDescription(string $currentDescription, array $propertyData): ?string
    {
        $type     = $propertyData['type'] ?? 'logement';
        $commune  = $propertyData['commune'] ?? '';
        $quartier = $propertyData['quartier'] ?? '';

        $prompt = <<<PROMPT
Tu es un rédacteur immobilier expert à Abidjan. Améliore cette description d'annonce.

Description actuelle :
"{$currentDescription}"

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

        return $this->complete($prompt, 'listing_improve', maxTokens: 800);
    }

    // ====================================================================
    // CONTRATS DE BAIL
    // ====================================================================

    /**
     * Générer des clauses spéciales pour un contrat de bail.
     */
    public function generateContractClauses(array $contractData): ?string
    {
        $type        = $contractData['lease_type'] ?? 'monthly';
        $rent        = $contractData['monthly_rent'] ?? 0;
        $deposit     = $contractData['deposit_amount'] ?? 0;
        $residence   = $contractData['residence_name'] ?? 'la résidence';
        $commune     = $contractData['commune'] ?? 'Abidjan';
        $services    = $contractData['included_services'] ?? [];
        $hasParking  = in_array('parking', array_map('strtolower', $services));

        $typeLabel = match ($type) {
            'short_term' => 'location courte durée (meublé)',
            'monthly'    => 'location mensuelle',
            'fixed_term' => 'location durée déterminée',
            default      => 'location',
        };

        $servicesText = count($services) > 0
            ? 'Services inclus : '.implode(', ', $services)
            : 'Aucun service inclus spécifié';

        $prompt = <<<PROMPT
Tu es un juriste spécialisé en droit immobilier ivoirien (OHADA). Rédige des clauses particulières pour un contrat de bail.

Informations du contrat :
- Type : {$typeLabel}
- Loyer : {$rent} FCFA/mois
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

        return $this->complete($prompt, 'contract_clauses', maxTokens: 600);
    }

    /**
     * Suggérer des services inclus pour un contrat.
     */
    public function suggestContractServices(array $contractData): ?array
    {
        $type     = $contractData['lease_type'] ?? 'monthly';
        $commune  = $contractData['commune'] ?? 'Abidjan';
        $rent     = $contractData['monthly_rent'] ?? 0;

        $prompt = <<<PROMPT
Tu es un expert en gestion immobilière à Abidjan. Pour un contrat de bail ({$type}) avec un loyer de {$rent} FCFA/mois à {$commune}, suggère les services qui devraient être inclus dans le loyer.

Retourne UNIQUEMENT une liste JSON de services, sans explication.
Exemple : ["Électricité", "Eau", "Wifi", "Gardiennage"]

Maximum 8 services pertinents pour le type de bien et le standing (basé sur le loyer).

Liste JSON :
PROMPT;

        $result = $this->complete($prompt, 'contract_services', maxTokens: 100);

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
     */
    private function complete(string $prompt, string $cachePrefix = '', int $maxTokens = 500): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        // Cache pour éviter les appels redondants (5 min)
        $cacheKey = $cachePrefix.'_'.md5($prompt);
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
        } catch (\Throwable $e) {
            Log::error('AI Assistant: Exception', [
                'provider' => $this->provider,
                'message'  => $e->getMessage(),
            ]);
        }

        return null;
    }

    // ====================================================================
    // CHATBOT CONVERSATION
    // ====================================================================

    /**
     * Conversation multi-tours avec l'IA (chatbot locataire 24/7).
     *
     * @param  array  $messages  Historique [{role: 'user'|'assistant', content: '...'}]
     * @param  array  $context   Contexte optionnel (résidence courante, commune, budget…)
     * @return string|null
     */
    public function chat(array $messages, array $context = []): ?string
    {
        if (! $this->isAvailable()) {
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
            Log::error('AI Chatbot: Exception', [
                'provider' => $this->provider,
                'error'    => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildChatbotSystemPrompt(array $context): string
    {
        $commune  = $context['commune'] ?? '';
        $budget   = $context['budget'] ?? '';
        $residence = $context['residence'] ?? '';

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
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
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
            ],
        ]);

        if ($response->successful()) {
            return $response->json('candidates.0.content.parts.0.text');
        }

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
            ],
        ]);

        if ($response->successful()) {
            $text = $response->json('candidates.0.content.parts.0.text');
            if ($text) {
                return $text;
            }
        }

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
        $type         = $data['type'] ?? 'logement';
        $typeLocation = $data['type_location'] ?? 'residence_meublee';
        $commune      = $data['commune'] ?? '';
        $quartier     = $data['quartier'] ?? '';
        $bedrooms     = $data['bedrooms'] ?? '';
        $bathrooms    = $data['bathrooms'] ?? '';
        $surface      = $data['surface_area'] ?? '';
        $price        = $data['price'] ?? '';
        $amenities    = $data['amenities'] ?? [];
        $name         = $data['name'] ?? '';

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
            ? 'Équipements : '.implode(', ', $amenities)
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
