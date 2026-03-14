<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentimentAnalysisService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://language.googleapis.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.google_cloud_vision.api_key', '');
    }

    // ─────────────────────────────────────────────
    // SENTIMENT ANALYSIS
    // ─────────────────────────────────────────────

    /**
     * Analyser le sentiment d'un texte.
     *
     * @param  string  $text  Le texte à analyser
     * @return array   ['score' => float(-1 à 1), 'magnitude' => float(0+), 'label' => string]
     */
    public function analyzeSentiment(string $text): array
    {
        if (empty(trim($text)) || strlen($text) < 10) {
            return $this->defaultResult();
        }

        try {
            $response = Http::post("{$this->baseUrl}/documents:analyzeSentiment?key={$this->apiKey}", [
                'document' => [
                    'type' => 'PLAIN_TEXT',
                    'language' => 'fr',
                    'content' => $text,
                ],
                'encodingType' => 'UTF8',
            ]);

            if (! $response->successful()) {
                Log::warning('Google NLP Sentiment API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return $this->defaultResult();
            }

            $data = $response->json();
            $sentiment = $data['documentSentiment'] ?? null;

            if (! $sentiment) {
                return $this->defaultResult();
            }

            $score = $sentiment['score'] ?? 0; // -1.0 (négatif) à 1.0 (positif)
            $magnitude = $sentiment['magnitude'] ?? 0; // Force du sentiment

            return [
                'score' => round($score, 3),
                'magnitude' => round($magnitude, 3),
                'label' => $this->getLabel($score),
                'sentences' => $this->extractSentences($data['sentences'] ?? []),
            ];
        } catch (\Exception $e) {
            Log::error('Google NLP Sentiment exception', ['error' => $e->getMessage()]);
            return $this->defaultResult();
        }
    }

    // ─────────────────────────────────────────────
    // ENTITY ANALYSIS
    // ─────────────────────────────────────────────

    /**
     * Extraire les entités (lieux, personnes, etc.) d'un texte.
     */
    public function analyzeEntities(string $text): array
    {
        if (empty(trim($text))) {
            return [];
        }

        try {
            $response = Http::post("{$this->baseUrl}/documents:analyzeEntities?key={$this->apiKey}", [
                'document' => [
                    'type' => 'PLAIN_TEXT',
                    'language' => 'fr',
                    'content' => $text,
                ],
                'encodingType' => 'UTF8',
            ]);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();

            return collect($data['entities'] ?? [])
                ->map(fn ($entity) => [
                    'name' => $entity['name'],
                    'type' => $entity['type'], // PERSON, LOCATION, ORGANIZATION, EVENT, etc.
                    'salience' => round($entity['salience'] ?? 0, 3),
                    'sentiment' => [
                        'score' => round($entity['sentiment']['score'] ?? 0, 3),
                        'magnitude' => round($entity['sentiment']['magnitude'] ?? 0, 3),
                    ],
                ])
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Google NLP Entities exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ─────────────────────────────────────────────
    // REVIEW-SPECIFIC METHODS
    // ─────────────────────────────────────────────

    /**
     * Analyser un avis de résidence.
     * Retourne le score de sentiment + des flags pour modération.
     */
    public function analyzeReview(string $comment): array
    {
        $sentiment = $this->analyzeSentiment($comment);

        return [
            'sentiment_score' => $sentiment['score'],
            'sentiment_magnitude' => $sentiment['magnitude'],
            'sentiment_label' => $sentiment['label'],
            'needs_moderation' => $this->needsModeration($sentiment),
            'is_very_negative' => $sentiment['score'] < -0.6,
            'is_very_positive' => $sentiment['score'] > 0.6,
        ];
    }

    /**
     * Déterminer si un avis nécessite une modération manuelle.
     */
    protected function needsModeration(array $sentiment): bool
    {
        // Très négatif avec forte conviction → modération requise
        if ($sentiment['score'] < -0.5 && $sentiment['magnitude'] > 1.0) {
            return true;
        }

        // Score extrêmement négatif dans tous les cas
        if ($sentiment['score'] < -0.7) {
            return true;
        }

        return false;
    }

    // ─────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────

    /**
     * Résultat par défaut quand l'analyse échoue.
     */
    protected function defaultResult(): array
    {
        return [
            'score' => 0,
            'magnitude' => 0,
            'label' => 'neutre',
            'sentences' => [],
        ];
    }

    /**
     * Convertir un score numérique en label lisible.
     */
    protected function getLabel(float $score): string
    {
        if ($score >= 0.5) {
            return 'très_positif';
        }
        if ($score >= 0.1) {
            return 'positif';
        }
        if ($score > -0.1) {
            return 'neutre';
        }
        if ($score > -0.5) {
            return 'négatif';
        }

        return 'très_négatif';
    }

    /**
     * Extraire les sentiments par phrase.
     */
    protected function extractSentences(array $sentences): array
    {
        return collect($sentences)
            ->map(fn ($sentence) => [
                'text' => $sentence['text']['content'] ?? '',
                'score' => round($sentence['sentiment']['score'] ?? 0, 3),
                'magnitude' => round($sentence['sentiment']['magnitude'] ?? 0, 3),
            ])
            ->toArray();
    }
}
