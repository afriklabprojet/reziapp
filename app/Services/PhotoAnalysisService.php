<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'analyse de photos via Google Cloud Vision API.
 *
 * Fonctionnalités :
 *  1. SafeSearch — modération automatique (contenu inapproprié)
 *  2. Label Detection — auto-tagging des pièces (chambre, cuisine, piscine…)
 *  3. Qualité photo — détection flou, sous-exposition, non-immobilier
 *  4. OCR — extraction texte sur reçus de paiement
 *  5. Détection doublons — hash visuel pour repérer les photos volées/réutilisées
 */
class PhotoAnalysisService
{
    private string $apiKey;
    private string $apiUrl = 'https://vision.googleapis.com/v1/images:annotate';

    /**
     * Labels Vision → catégories immobilières en français.
     */
    private const ROOM_LABELS = [
        // Salon / Séjour
        'living room'   => 'Salon',
        'family room'   => 'Salon',
        'sitting room'  => 'Salon',
        'interior design' => 'Intérieur',

        // Chambre
        'bedroom'       => 'Chambre',
        'bed'           => 'Chambre',
        'bed frame'     => 'Chambre',

        // Cuisine
        'kitchen'       => 'Cuisine',
        'countertop'    => 'Cuisine',
        'cabinetry'     => 'Cuisine',

        // Salle de bain
        'bathroom'      => 'Salle de bain',
        'bathtub'       => 'Salle de bain',
        'shower'        => 'Salle de bain',
        'sink'          => 'Salle de bain',
        'toilet'        => 'Toilettes',
        'plumbing fixture' => 'Salle de bain',

        // Extérieur
        'swimming pool' => 'Piscine',
        'pool'          => 'Piscine',
        'garden'        => 'Jardin',
        'yard'          => 'Jardin',
        'patio'         => 'Terrasse',
        'balcony'       => 'Balcon',
        'terrace'       => 'Terrasse',
        'deck'          => 'Terrasse',
        'parking'       => 'Parking',
        'garage'        => 'Garage',

        // Bâtiment
        'building'      => 'Façade',
        'house'         => 'Façade',
        'apartment'     => 'Façade',
        'real estate'   => 'Vue extérieure',
        'property'      => 'Vue extérieure',
        'facade'        => 'Façade',
        'architecture'  => 'Façade',

        // Salle à manger
        'dining room'   => 'Salle à manger',
        'dining table'  => 'Salle à manger',

        // Mobilier / Général
        'furniture'     => 'Meublé',
        'table'         => 'Mobilier',
        'chair'         => 'Mobilier',
        'couch'         => 'Salon',
        'sofa'          => 'Salon',

        // Espaces
        'hallway'       => 'Couloir',
        'corridor'      => 'Couloir',
        'staircase'     => 'Escalier',
        'roof'          => 'Toiture',
        'ceiling'       => 'Intérieur',
        'floor'         => 'Intérieur',
        'window'        => 'Vue',
        'door'          => 'Entrée',
    ];

    /**
     * Labels explicitement NON-immobiliers (la photo n'est pas d'un bien).
     */
    private const NON_PROPERTY_LABELS = [
        'selfie', 'person', 'face', 'portrait', 'people',
        'screenshot', 'text', 'document', 'meme', 'cartoon',
        'animal', 'cat', 'dog', 'food', 'meal', 'vehicle',
        'car', 'motorcycle', 'landscape', 'mountain', 'beach',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.google_cloud_vision.api_key', '');
    }

    /**
     * Vérifie si le service est disponible.
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    // ========================================================================
    // 1. ANALYSE COMPLÈTE D'UNE PHOTO DE RÉSIDENCE
    // ========================================================================

    /**
     * Analyse complète : SafeSearch + Labels + Qualité en un seul appel API.
     *
     * @return array{
     *   safe_search: array,
     *   labels: array,
     *   tags: string[],
     *   room_type: ?string,
     *   quality: array,
     *   is_property_photo: bool,
     *   moderation: array{approved: bool, reason: ?string},
     *   image_hash: ?string
     * }
     */
    public function analyzeResidencePhoto(string $imageContent): array
    {
        if (!$this->isAvailable()) {
            return $this->emptyAnalysis('Service Cloud Vision non configuré');
        }

        // Un seul appel API avec toutes les features nécessaires
        $response = $this->callVisionApi([
            [
                'image'    => ['content' => base64_encode($imageContent)],
                'features' => [
                    ['type' => 'SAFE_SEARCH_DETECTION'],
                    ['type' => 'LABEL_DETECTION', 'maxResults' => 20],
                    ['type' => 'IMAGE_PROPERTIES', 'maxResults' => 5],
                ],
            ],
        ]);

        if (!$response || !isset($response['responses'][0])) {
            return $this->emptyAnalysis('Erreur API Cloud Vision');
        }

        $data = $response['responses'][0];

        // 1. SafeSearch
        $safeSearch = $this->parseSafeSearch($data['safeSearchAnnotation'] ?? []);

        // 2. Labels + Room tagging
        $labelResult = $this->parseLabels($data['labelAnnotations'] ?? []);

        // 3. Qualité image (couleurs dominantes, luminosité)
        $quality = $this->parseImageProperties($data['imagePropertiesAnnotation'] ?? []);

        // 4. Modération finale
        $moderation = $this->determineModeration($safeSearch, $labelResult, $quality);

        // 5. Hash visuel pour détection doublons
        $imageHash = $this->computeImageHash($imageContent);

        return [
            'safe_search'      => $safeSearch,
            'labels'           => $labelResult['raw_labels'],
            'tags'             => $labelResult['tags'],
            'room_type'        => $labelResult['room_type'],
            'quality'          => $quality,
            'is_property_photo' => $labelResult['is_property'],
            'moderation'       => $moderation,
            'image_hash'       => $imageHash,
        ];
    }

    // ========================================================================
    // 2. SAFESEARCH — MODÉRATION CONTENU
    // ========================================================================

    /**
     * Parser les résultats SafeSearch.
     */
    private function parseSafeSearch(array $annotation): array
    {
        $levels = ['UNKNOWN' => 0, 'VERY_UNLIKELY' => 1, 'UNLIKELY' => 2, 'POSSIBLE' => 3, 'LIKELY' => 4, 'VERY_LIKELY' => 5];

        return [
            'adult'    => $annotation['adult'] ?? 'UNKNOWN',
            'spoof'    => $annotation['spoof'] ?? 'UNKNOWN',
            'medical'  => $annotation['medical'] ?? 'UNKNOWN',
            'violence' => $annotation['violence'] ?? 'UNKNOWN',
            'racy'     => $annotation['racy'] ?? 'UNKNOWN',
            'adult_score'    => $levels[$annotation['adult'] ?? 'UNKNOWN'] ?? 0,
            'violence_score' => $levels[$annotation['violence'] ?? 'UNKNOWN'] ?? 0,
            'racy_score'     => $levels[$annotation['racy'] ?? 'UNKNOWN'] ?? 0,
        ];
    }

    // ========================================================================
    // 3. LABEL DETECTION — AUTO-TAGGING
    // ========================================================================

    /**
     * Parser les labels détectés et les convertir en tags immobiliers.
     */
    private function parseLabels(array $annotations): array
    {
        $rawLabels = [];
        $tags = [];
        $roomType = null;
        $isProperty = false;
        $nonPropertyCount = 0;
        $propertyCount = 0;

        foreach ($annotations as $label) {
            $description = strtolower($label['description'] ?? '');
            $score = $label['score'] ?? 0;

            $rawLabels[] = [
                'label' => $description,
                'score' => round($score, 3),
            ];

            // Skip les labels à faible confiance
            if ($score < 0.6) {
                continue;
            }

            // Mapper vers une catégorie immobilière
            foreach (self::ROOM_LABELS as $keyword => $frenchTag) {
                if (str_contains($description, $keyword)) {
                    if (!in_array($frenchTag, $tags)) {
                        $tags[] = $frenchTag;
                    }
                    // Le premier tag haute confiance devient le room_type
                    if (!$roomType && $score >= 0.7) {
                        $roomType = $frenchTag;
                    }
                    $propertyCount++;
                    break;
                }
            }

            // Vérifier si c'est un label non-immobilier
            foreach (self::NON_PROPERTY_LABELS as $nonLabel) {
                if (str_contains($description, $nonLabel)) {
                    $nonPropertyCount++;
                    break;
                }
            }
        }

        // Déterminer si la photo montre un bien immobilier
        $isProperty = $propertyCount >= 2 || ($propertyCount >= 1 && $nonPropertyCount === 0);

        return [
            'raw_labels'  => $rawLabels,
            'tags'        => $tags,
            'room_type'   => $roomType,
            'is_property' => $isProperty,
            'stats'       => [
                'property_labels'     => $propertyCount,
                'non_property_labels' => $nonPropertyCount,
            ],
        ];
    }

    // ========================================================================
    // 4. QUALITÉ IMAGE
    // ========================================================================

    /**
     * Analyser les propriétés de l'image (luminosité, contraste, couleurs).
     */
    private function parseImageProperties(array $properties): array
    {
        $colors = $properties['dominantColors']['colors'] ?? [];

        if (empty($colors)) {
            return [
                'brightness' => null,
                'is_dark'    => false,
                'is_blurry'  => false,
                'score'      => 50,
                'issues'     => [],
            ];
        }

        // Calculer la luminosité moyenne pondérée
        $totalWeight = 0;
        $weightedBrightness = 0;
        $colorVariety = count($colors);

        foreach ($colors as $color) {
            $rgb = $color['color'] ?? [];
            $fraction = $color['pixelFraction'] ?? 0;
            $r = $rgb['red'] ?? 0;
            $g = $rgb['green'] ?? 0;
            $b = $rgb['blue'] ?? 0;

            // Luminosité perçue (formule ITU-R BT.709)
            $brightness = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
            $weightedBrightness += $brightness * $fraction;
            $totalWeight += $fraction;
        }

        $avgBrightness = $totalWeight > 0 ? $weightedBrightness / $totalWeight : 128;
        $issues = [];
        $qualityScore = 100;

        // Trop sombre ?
        $isDark = $avgBrightness < 50;
        if ($isDark) {
            $issues[] = 'Photo trop sombre';
            $qualityScore -= 30;
        }

        // Trop claire / surexposée ?
        if ($avgBrightness > 230) {
            $issues[] = 'Photo surexposée';
            $qualityScore -= 20;
        }

        // Peu de variété de couleurs → peut indiquer une image monotone
        if ($colorVariety <= 2) {
            $issues[] = 'Image avec peu de couleurs (possible capture écran ou image synthétique)';
            $qualityScore -= 15;
        }

        return [
            'brightness'    => round($avgBrightness, 1),
            'color_variety' => $colorVariety,
            'is_dark'       => $isDark,
            'is_overexposed' => $avgBrightness > 230,
            'score'         => max(0, $qualityScore),
            'issues'        => $issues,
        ];
    }

    // ========================================================================
    // 5. OCR — EXTRACTION TEXTE SUR REÇUS DE PAIEMENT
    // ========================================================================

    /**
     * Extraire les informations d'un reçu de paiement Mobile Money.
     *
     * @return array{
     *   raw_text: string,
     *   amount: ?int,
     *   reference: ?string,
     *   phone: ?string,
     *   provider: ?string,
     *   date: ?string,
     *   is_valid_receipt: bool
     * }
     */
    public function extractPaymentReceipt(string $imageContent): array
    {
        if (!$this->isAvailable()) {
            return [
                'raw_text' => '',
                'amount' => null,
                'reference' => null,
                'phone' => null,
                'provider' => null,
                'date' => null,
                'is_valid_receipt' => false,
                'error' => 'Service Cloud Vision non configuré',
            ];
        }

        $response = $this->callVisionApi([
            [
                'image'    => ['content' => base64_encode($imageContent)],
                'features' => [
                    ['type' => 'TEXT_DETECTION', 'maxResults' => 10],
                    ['type' => 'DOCUMENT_TEXT_DETECTION', 'maxResults' => 1],
                ],
            ],
        ]);

        if (!$response || !isset($response['responses'][0])) {
            return [
                'raw_text' => '',
                'amount' => null,
                'reference' => null,
                'phone' => null,
                'provider' => null,
                'date' => null,
                'is_valid_receipt' => false,
                'error' => 'Erreur API Cloud Vision',
            ];
        }

        $data = $response['responses'][0];
        $rawText = $data['fullTextAnnotation']['text']
            ?? $data['textAnnotations'][0]['description']
            ?? '';

        return $this->parsePaymentReceipt($rawText);
    }

    /**
     * Parser le texte OCR d'un reçu Mobile Money.
     */
    private function parsePaymentReceipt(string $text): array
    {
        $result = [
            'raw_text'         => $text,
            'amount'           => null,
            'reference'        => null,
            'phone'            => null,
            'provider'         => null,
            'date'             => null,
            'is_valid_receipt' => false,
        ];

        if (empty($text)) {
            return $result;
        }

        $upper = mb_strtoupper($text);

        // Détecter le provider
        if (preg_match('/WAVE/i', $text)) {
            $result['provider'] = 'wave';
        } elseif (preg_match('/ORANGE\s*MONEY|OM\b/i', $text)) {
            $result['provider'] = 'orange_money';
        } elseif (preg_match('/MTN\s*MO(BILE)?\s*MONEY|MOMO/i', $text)) {
            $result['provider'] = 'mtn_momo';
        } elseif (preg_match('/MOOV\s*MONEY|FLOOZ/i', $text)) {
            $result['provider'] = 'moov_money';
        }

        // Extraire le montant (formats CFA / FCFA / XOF)
        // Ex: "25 000 FCFA", "25.000 F CFA", "Montant: 25000"
        if (preg_match('/(?:MONTANT|AMOUNT|TOTAL)[:\s]*([0-9\s.]+)/i', $text, $m)) {
            $result['amount'] = (int) preg_replace('/[^0-9]/', '', $m[1]);
        } elseif (preg_match('/([0-9]{1,3}(?:[\s.]?[0-9]{3})*)\s*(?:F\s*CFA|FCFA|XOF|CFA|F)/i', $text, $m)) {
            $result['amount'] = (int) preg_replace('/[^0-9]/', '', $m[1]);
        }

        // Extraire la référence / ID transaction
        if (preg_match('/(?:REF|REFERENCE|ID|TRANS(?:ACTION)?)[:\s#]*([A-Z0-9\-]{6,30})/i', $text, $m)) {
            $result['reference'] = $m[1];
        } elseif (preg_match('/\b([A-Z]{2,4}[0-9]{8,20})\b/', $upper, $m)) {
            $result['reference'] = $m[1];
        }

        // Extraire le numéro de téléphone (format CI : +225 XX XX XX XX XX ou 07XXXXXXXX)
        if (preg_match('/(?:\+?225\s?)?([057]\d[\s.-]?\d{2}[\s.-]?\d{2}[\s.-]?\d{2}[\s.-]?\d{2})/', $text, $m)) {
            $result['phone'] = preg_replace('/[\s.\-]/', '', $m[1]);
        }

        // Extraire la date
        if (preg_match('/(\d{2})[\/\-.](\d{2})[\/\-.](\d{4})/', $text, $m)) {
            $result['date'] = "{$m[3]}-{$m[2]}-{$m[1]}";
        } elseif (preg_match('/(\d{4})[\/\-.](\d{2})[\/\-.](\d{2})/', $text, $m)) {
            $result['date'] = "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        // Un reçu valid a au minimum un montant OU une référence + un provider
        $result['is_valid_receipt'] = ($result['amount'] !== null || $result['reference'] !== null)
            && $result['provider'] !== null;

        return $result;
    }

    // ========================================================================
    // 6. DÉTECTION DOUBLONS — HASH VISUEL
    // ========================================================================

    /**
     * Calculer un hash perceptuel de l'image (pHash simplifié).
     *
     * Réduit l'image à 8x8 niveaux de gris, compare à la moyenne →
     * produit un hash 64-bit. Deux images similaires auront un hash
     * avec une faible distance de Hamming.
     */
    public function computeImageHash(string $imageContent): ?string
    {
        $image = @imagecreatefromstring($imageContent);
        if (!$image) {
            return null;
        }

        // Réduire à 8x8
        $small = imagecreatetruecolor(8, 8);
        imagecopyresampled($small, $image, 0, 0, 0, 0, 8, 8, imagesx($image), imagesy($image));

        // Convertir en niveaux de gris
        $pixels = [];
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $pixels[] = (int) (0.299 * $r + 0.587 * $g + 0.114 * $b);
            }
        }

        imagedestroy($image);
        imagedestroy($small);

        // Calculer la moyenne
        $avg = array_sum($pixels) / count($pixels);

        // Générer le hash binaire
        $hash = '';
        foreach ($pixels as $pixel) {
            $hash .= $pixel >= $avg ? '1' : '0';
        }

        // Convertir en hexadécimal (16 chars = 64 bits)
        return str_pad(base_convert(substr($hash, 0, 32), 2, 16), 8, '0', STR_PAD_LEFT)
            .str_pad(base_convert(substr($hash, 32, 32), 2, 16), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Comparer deux hash perceptuels (distance de Hamming).
     *
     * @return int Distance (0 = identique, <5 = très similaire, <10 = similaire)
     */
    public static function hammingDistance(string $hash1, string $hash2): int
    {
        if (strlen($hash1) !== strlen($hash2)) {
            return PHP_INT_MAX;
        }

        $bin1 = str_pad(base_convert(substr($hash1, 0, 8), 16, 2), 32, '0', STR_PAD_LEFT)
            .str_pad(base_convert(substr($hash1, 8, 8), 16, 2), 32, '0', STR_PAD_LEFT);
        $bin2 = str_pad(base_convert(substr($hash2, 0, 8), 16, 2), 32, '0', STR_PAD_LEFT)
            .str_pad(base_convert(substr($hash2, 8, 8), 16, 2), 32, '0', STR_PAD_LEFT);

        $distance = 0;
        for ($i = 0; $i < strlen($bin1); $i++) {
            if ($bin1[$i] !== $bin2[$i]) {
                $distance++;
            }
        }

        return $distance;
    }

    /**
     * Chercher les photos visuellement similaires dans la base.
     *
     * @return array Photos similaires trouvées
     */
    public function findDuplicates(string $imageHash, ?int $excludePhotoId = null, int $threshold = 5): array
    {
        $query = \App\Models\Photo::whereNotNull('image_hash')
            ->where('image_hash', '!=', '');

        if ($excludePhotoId) {
            $query->where('id', '!=', $excludePhotoId);
        }

        $duplicates = [];
        foreach ($query->cursor() as $photo) {
            $distance = self::hammingDistance($imageHash, $photo->image_hash);
            if ($distance <= $threshold) {
                $duplicates[] = [
                    'photo_id'     => $photo->id,
                    'residence_id' => $photo->residence_id,
                    'distance'     => $distance,
                    'path'         => $photo->path,
                ];
            }
        }

        // Trier par distance (plus similaire en premier)
        usort($duplicates, fn ($a, $b) => $a['distance'] <=> $b['distance']);

        return $duplicates;
    }

    // ========================================================================
    // DÉCISION DE MODÉRATION
    // ========================================================================

    /**
     * Déterminer si la photo est approuvée, en attente de revue ou rejetée.
     */
    private function determineModeration(array $safeSearch, array $labelResult, array $quality): array
    {
        // Rejet immédiat : contenu explicite
        if (in_array($safeSearch['adult'], ['LIKELY', 'VERY_LIKELY'])) {
            return ['approved' => false, 'status' => 'rejected', 'reason' => 'Contenu adulte détecté'];
        }
        if (in_array($safeSearch['violence'], ['LIKELY', 'VERY_LIKELY'])) {
            return ['approved' => false, 'status' => 'rejected', 'reason' => 'Contenu violent détecté'];
        }

        // Revue manuelle : contenu douteux
        if (in_array($safeSearch['adult'], ['POSSIBLE'])) {
            return ['approved' => false, 'status' => 'review', 'reason' => 'Contenu potentiellement inapproprié'];
        }
        if (in_array($safeSearch['racy'], ['LIKELY', 'VERY_LIKELY'])) {
            return ['approved' => false, 'status' => 'review', 'reason' => 'Contenu suggestif détecté'];
        }

        // Avertissement qualité
        if ($quality['score'] < 30) {
            return ['approved' => false, 'status' => 'review', 'reason' => 'Qualité photo insuffisante: '.implode(', ', $quality['issues'])];
        }

        // Avertissement non-immobilier
        if (!$labelResult['is_property'] && $labelResult['stats']['non_property_labels'] >= 3) {
            return ['approved' => false, 'status' => 'review', 'reason' => 'La photo ne semble pas montrer un bien immobilier'];
        }

        return ['approved' => true, 'status' => 'approved', 'reason' => null];
    }

    /**
     * Résultat vide en cas d'erreur.
     */
    private function emptyAnalysis(string $error): array
    {
        return [
            'safe_search'       => [],
            'labels'            => [],
            'tags'              => [],
            'room_type'         => null,
            'quality'           => ['score' => 50, 'issues' => []],
            'is_property_photo' => true, // présumer OK si pas d'API
            'moderation'        => ['approved' => true, 'status' => 'skipped', 'reason' => $error],
            'image_hash'        => null,
        ];
    }

    // ========================================================================
    // APPEL API
    // ========================================================================

    /**
     * Appeler l'API Google Cloud Vision.
     */
    private function callVisionApi(array $requests): ?array
    {
        try {
            $response = Http::timeout(30)
                ->retry(2, 1000)
                ->post("{$this->apiUrl}?key={$this->apiKey}", [
                    'requests' => $requests,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('PhotoAnalysis: Erreur API Cloud Vision', [
                'status' => $response->status(),
                'body'   => \Illuminate\Support\Str::limit($response->body(), 500),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PhotoAnalysis: Exception appel API', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
