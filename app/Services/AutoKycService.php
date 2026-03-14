<?php

namespace App\Services;

use App\Models\IdentityVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service de vérification KYC automatique via Google Cloud Vision API.
 *
 * Fonctionnalités :
 * - OCR (extraction de texte) sur les documents d'identité (CNI, passeport)
 * - Détection de visage dans le document et le selfie
 * - Comparaison des données extraites avec les données saisies par l'utilisateur
 * - Score de confiance et décision automatique (approuver / revue manuelle / rejeter)
 *
 * Nécessite : GOOGLE_CLOUD_VISION_API_KEY dans .env
 * + activer l'API Cloud Vision dans la console Google Cloud.
 */
class AutoKycService
{
    protected string $apiKey;
    protected string $apiUrl = 'https://vision.googleapis.com/v1/images:annotate';
    protected array $config;

    public function __construct()
    {
        $this->apiKey = config('services.google_cloud_vision.api_key', '');
        $this->config = config('rezi.kyc.auto_verification', []);
    }

    /**
     * Point d'entrée — traiter une vérification d'identité.
     */
    public function process(IdentityVerification $verification): array
    {
        if (empty($this->apiKey)) {
            Log::warning('AutoKYC: Clé API Cloud Vision absente, passage en revue manuelle.', [
                'verification_id' => $verification->id,
            ]);

            return $this->fallbackToManualReview($verification, 'Clé API Cloud Vision non configurée');
        }

        if (!$this->isEnabled()) {
            return $this->fallbackToManualReview($verification, 'Vérification automatique désactivée');
        }

        $verification->update(['status' => 'processing']);

        try {
            // Étape 1 : OCR du document (recto)
            $ocrResult = $this->extractDocumentText($verification);

            // Étape 2 : Détection de visage dans le document
            $documentFaceResult = $this->detectFace($verification->document_front);

            // Étape 3 : Détection de visage dans le selfie
            $selfieFaceResult = $this->detectFace($verification->selfie_photo);

            // Étape 4 : Analyser et scorer
            $analysis = $this->analyzeResults($verification, $ocrResult, $documentFaceResult, $selfieFaceResult);

            // Étape 5 : Enregistrer les résultats
            $this->saveResults($verification, $analysis);

            // Étape 6 : Décision automatique
            return $this->makeDecision($verification, $analysis);

        } catch (\Exception $e) {
            Log::error('AutoKYC: Erreur lors du traitement', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 500),
            ]);

            return $this->fallbackToManualReview($verification, 'Erreur technique : ' . Str::limit($e->getMessage(), 100));
        }
    }

    /**
     * Vérifier si le service est activé.
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    // ==========================================
    // OCR — EXTRACTION DE TEXTE
    // ==========================================

    /**
     * Extraire le texte du document via Google Cloud Vision OCR.
     */
    protected function extractDocumentText(IdentityVerification $verification): array
    {
        $imagePaths = [$verification->document_front];
        if ($verification->document_back) {
            $imagePaths[] = $verification->document_back;
        }

        $allText = '';
        $rawResponses = [];

        foreach ($imagePaths as $imagePath) {
            $imageContent = $this->getImageContent($imagePath);

            if (!$imageContent) {
                Log::warning('AutoKYC: Image introuvable', ['path' => $imagePath]);
                continue;
            }

            $response = $this->callVisionApi([
                [
                    'image' => ['content' => base64_encode($imageContent)],
                    'features' => [
                        ['type' => 'TEXT_DETECTION', 'maxResults' => 10],
                        ['type' => 'DOCUMENT_TEXT_DETECTION', 'maxResults' => 1],
                    ],
                ],
            ]);

            if ($response && isset($response['responses'][0])) {
                $responseData = $response['responses'][0];
                $rawResponses[] = $responseData;

                // Texte complet détecté
                if (isset($responseData['fullTextAnnotation']['text'])) {
                    $allText .= ' ' . $responseData['fullTextAnnotation']['text'];
                } elseif (isset($responseData['textAnnotations'][0]['description'])) {
                    $allText .= ' ' . $responseData['textAnnotations'][0]['description'];
                }
            }
        }

        $allText = trim($allText);

        // Parser les données extraites
        $parsed = $this->parseDocumentText($allText, $verification->document_type);

        return [
            'raw_text' => $allText,
            'parsed' => $parsed,
            'raw_responses' => $rawResponses,
            'has_text' => !empty($allText),
        ];
    }

    /**
     * Parser le texte OCR pour extraire nom, numéro, dates etc.
     */
    protected function parseDocumentText(string $text, string $documentType): array
    {
        $parsed = [
            'names' => [],
            'document_number' => null,
            'dates' => [],
            'nationality' => null,
            'birth_date' => null,
            'expiry_date' => null,
            'confidence' => 0,
        ];

        if (empty($text)) {
            return $parsed;
        }

        $text = mb_strtoupper($text);
        $lines = array_filter(array_map('trim', preg_split('/[\n\r]+/', $text)));

        // Chercher le numéro de document selon le type
        if ($documentType === 'cni') {
            // Formats CNI ivoirienne : C + 9 chiffres, CI + 9-10 chiffres, ou séquence numérique
            foreach ($lines as $line) {
                if (preg_match('/\b(C\d{9})\b/i', $line, $m)) {
                    $parsed['document_number'] = $m[1];
                    break;
                }
                if (preg_match('/\b(CI\d{9,10})\b/i', $line, $m)) {
                    $parsed['document_number'] = $m[1];
                    break;
                }
            }
        } elseif ($documentType === 'passport') {
            // Formats passeport ivoirien : 2 chiffres + 2 lettres + 5 chiffres, ou lettres + chiffres
            foreach ($lines as $line) {
                if (preg_match('/\b(\d{2}[A-Z]{2}\d{5})\b/', $line, $m)) {
                    $parsed['document_number'] = $m[1];
                    break;
                }
                if (preg_match('/\b([A-Z]{1,3}\d{6,9})\b/', $line, $m)) {
                    $parsed['document_number'] = $m[1];
                    break;
                }
            }
        }

        // Chercher les dates (formats courants : JJ/MM/AAAA, JJ-MM-AAAA, JJ.MM.AAAA)
        preg_match_all('/\b(\d{2})[\/\-\.](\d{2})[\/\-\.](\d{4})\b/', $text, $dateMatches, PREG_SET_ORDER);
        foreach ($dateMatches as $match) {
            $day = (int) $match[1];
            $month = (int) $match[2];
            $year = (int) $match[3];

            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && $year >= 1940 && $year <= 2040) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $parsed['dates'][] = $dateStr;

                // Date de naissance : avant 2010
                if ($year < 2010 && !$parsed['birth_date']) {
                    $parsed['birth_date'] = $dateStr;
                }
                // Date d'expiration : après aujourd'hui
                if ($year >= (int) date('Y') && !$parsed['expiry_date']) {
                    $parsed['expiry_date'] = $dateStr;
                }
            }
        }

        // Chercher des noms (lignes contenant uniquement des lettres et espaces, min 3 chars)
        foreach ($lines as $line) {
            $clean = preg_replace('/[^A-ZÉÈÊËÀÂÄÎÏÔÙÛÜÇÑ\s\'-]/u', '', $line);
            if (mb_strlen($clean) >= 3 && $clean === $line) {
                $parsed['names'][] = trim($clean);
            }
        }

        // Chercher nationalité ivoirienne
        if (preg_match('/IVOIRIENNE|C[ÔO]TE\s*D[\'`]?IVOIRE|IVORY\s*COAST|CIV/i', $text)) {
            $parsed['nationality'] = 'CI';
        }

        // Déterminer le document (mot-clé)
        $isDocument = preg_match('/CARTE\s*(NATIONALE|D.IDENTIT)|PASSEPORT|PASSPORT|REPUBLIC|IDENTIT[ÉE]/i', $text);

        // Calculer un score de confiance OCR
        $scoreFactors = 0;
        if (!empty($parsed['document_number'])) $scoreFactors += 30;
        if (!empty($parsed['names'])) $scoreFactors += 20;
        if (!empty($parsed['birth_date'])) $scoreFactors += 15;
        if (!empty($parsed['expiry_date'])) $scoreFactors += 15;
        if ($parsed['nationality'] === 'CI') $scoreFactors += 10;
        if ($isDocument) $scoreFactors += 10;

        $parsed['confidence'] = min(100, $scoreFactors);

        return $parsed;
    }

    // ==========================================
    // DÉTECTION DE VISAGE
    // ==========================================

    /**
     * Détecter les visages dans une image via Google Cloud Vision.
     */
    protected function detectFace(string $imagePath): array
    {
        $imageContent = $this->getImageContent($imagePath);

        if (!$imageContent) {
            return [
                'has_face' => false,
                'face_count' => 0,
                'confidence' => 0,
                'error' => 'Image introuvable',
            ];
        }

        $response = $this->callVisionApi([
            [
                'image' => ['content' => base64_encode($imageContent)],
                'features' => [
                    ['type' => 'FACE_DETECTION', 'maxResults' => 5],
                ],
            ],
        ]);

        if (!$response || !isset($response['responses'][0])) {
            return [
                'has_face' => false,
                'face_count' => 0,
                'confidence' => 0,
                'error' => 'Erreur API Vision',
            ];
        }

        $faces = $response['responses'][0]['faceAnnotations'] ?? [];
        $faceCount = count($faces);

        if ($faceCount === 0) {
            return [
                'has_face' => false,
                'face_count' => 0,
                'confidence' => 0,
            ];
        }

        // Prendre le visage principal (meilleur score)
        $mainFace = $faces[0];
        $detectionConfidence = $mainFace['detectionConfidence'] ?? 0;
        $landmarkingConfidence = $mainFace['landmarkingConfidence'] ?? 0;

        // Vérifications de qualité
        $underExposed = ($mainFace['underExposedLikelihood'] ?? 'UNKNOWN') !== 'VERY_LIKELY';
        $blurred = ($mainFace['blurredLikelihood'] ?? 'UNKNOWN') !== 'VERY_LIKELY';
        $headwear = ($mainFace['headwearLikelihood'] ?? 'UNKNOWN');

        return [
            'has_face' => true,
            'face_count' => $faceCount,
            'confidence' => round($detectionConfidence * 100, 1),
            'landmarking_confidence' => round($landmarkingConfidence * 100, 1),
            'well_exposed' => $underExposed,
            'not_blurred' => $blurred,
            'headwear' => $headwear,
            'joy' => $mainFace['joyLikelihood'] ?? 'UNKNOWN',
            'anger' => $mainFace['angerLikelihood'] ?? 'UNKNOWN',
            'pan_angle' => $mainFace['panAngle'] ?? null,
            'tilt_angle' => $mainFace['tiltAngle'] ?? null,
            'roll_angle' => $mainFace['rollAngle'] ?? null,
        ];
    }

    // ==========================================
    // ANALYSE ET SCORING
    // ==========================================

    /**
     * Analyser les résultats OCR + visage et calculer un score global.
     */
    protected function analyzeResults(
        IdentityVerification $verification,
        array $ocrResult,
        array $documentFaceResult,
        array $selfieFaceResult,
    ): array {
        $scores = [];
        $issues = [];
        $details = [];

        // --- Score OCR (max 35 points) ---
        $ocrScore = 0;
        $parsed = $ocrResult['parsed'] ?? [];

        if ($ocrResult['has_text']) {
            $ocrScore += 5; // Du texte est détecté

            // Numéro de document correspond ?
            if (!empty($parsed['document_number']) && $verification->document_number) {
                try {
                    $storedNumber = decrypt($verification->document_number);
                    $extractedNumber = preg_replace('/\s+/', '', $parsed['document_number']);
                    $storedClean = preg_replace('/\s+/', '', strtoupper($storedNumber));

                    if ($extractedNumber === $storedClean) {
                        $ocrScore += 15;
                        $details['document_number_match'] = true;
                    } elseif (similar_text($extractedNumber, $storedClean, $percent) && $percent > 80) {
                        $ocrScore += 8;
                        $details['document_number_partial_match'] = round($percent, 1);
                    } else {
                        $issues[] = 'Numéro de document OCR ne correspond pas au numéro saisi';
                        $details['document_number_match'] = false;
                    }
                } catch (\Exception $e) {
                    $ocrScore += 5; // Pas de comparaison possible
                    $details['document_number_decrypt_error'] = true;
                }
            } elseif (!empty($parsed['document_number'])) {
                $ocrScore += 5; // Numéro détecté mais pas de comparaison
            }

            // Noms détectés
            if (!empty($parsed['names'])) {
                $ocrScore += 5;
                $details['names_found'] = $parsed['names'];
            }

            // Dates détectées
            if (!empty($parsed['dates'])) {
                $ocrScore += 5;
            }

            // Le document contient des marqueurs d'authenticité
            if (($parsed['confidence'] ?? 0) >= 60) {
                $ocrScore += 5;
            }
        } else {
            $issues[] = 'Aucun texte détecté sur le document (image floue ou trop sombre ?)';
        }

        $scores['ocr'] = min(35, $ocrScore);

        // --- Score visage document (max 25 points) ---
        $docFaceScore = 0;
        if ($documentFaceResult['has_face']) {
            $docFaceScore += 10;
            if ($documentFaceResult['face_count'] === 1) {
                $docFaceScore += 5; // Un seul visage (attendu)
            } else {
                $issues[] = 'Plusieurs visages détectés sur le document (' . $documentFaceResult['face_count'] . ')';
            }
            if (($documentFaceResult['confidence'] ?? 0) >= 80) {
                $docFaceScore += 5;
            }
            if ($documentFaceResult['well_exposed'] ?? false) {
                $docFaceScore += 3;
            }
            if ($documentFaceResult['not_blurred'] ?? false) {
                $docFaceScore += 2;
            }
        } else {
            $issues[] = 'Aucun visage détecté sur le document';
        }

        $scores['document_face'] = min(25, $docFaceScore);

        // --- Score selfie (max 30 points) ---
        $selfieScore = 0;
        if ($selfieFaceResult['has_face']) {
            $selfieScore += 10;
            if ($selfieFaceResult['face_count'] === 1) {
                $selfieScore += 5; // Un seul visage
            } else {
                $issues[] = 'Plusieurs visages détectés dans le selfie (' . $selfieFaceResult['face_count'] . ')';
            }
            if (($selfieFaceResult['confidence'] ?? 0) >= 85) {
                $selfieScore += 5;
            }
            if ($selfieFaceResult['well_exposed'] ?? false) {
                $selfieScore += 3;
            }
            if ($selfieFaceResult['not_blurred'] ?? false) {
                $selfieScore += 2;
            }
            // Vérifier que le selfie est bien un portrait (inclinaison raisonnable)
            $panAngle = abs($selfieFaceResult['pan_angle'] ?? 0);
            $tiltAngle = abs($selfieFaceResult['tilt_angle'] ?? 0);
            if ($panAngle < 30 && $tiltAngle < 30) {
                $selfieScore += 5; // Visage bien orienté
            }
        } else {
            $issues[] = 'Aucun visage détecté dans le selfie';
        }

        $scores['selfie_face'] = min(30, $selfieScore);

        // --- Score de cohérence (max 10 points) ---
        $coherenceScore = 0;
        if ($documentFaceResult['has_face'] && $selfieFaceResult['has_face']) {
            $coherenceScore += 5; // Les deux images ont un visage
            if ($documentFaceResult['face_count'] === 1 && $selfieFaceResult['face_count'] === 1) {
                $coherenceScore += 5; // Un seul visage dans chaque image
            }
        }

        $scores['coherence'] = min(10, $coherenceScore);

        // --- Score total ---
        $totalScore = array_sum($scores);

        // --- Score de correspondance faciale (estimation basée sur les métadonnées) ---
        $faceMatchScore = 0.0;
        if ($documentFaceResult['has_face'] && $selfieFaceResult['has_face']) {
            // Score basé sur les confiances de détection
            $docConf = $documentFaceResult['confidence'] ?? 0;
            $selfieConf = $selfieFaceResult['confidence'] ?? 0;
            $faceMatchScore = round(min($docConf, $selfieConf) / 100, 2);

            // Bonus si les deux ont un seul visage bien détecté
            if ($documentFaceResult['face_count'] === 1 && $selfieFaceResult['face_count'] === 1) {
                $faceMatchScore = round(min(1.0, $faceMatchScore + 0.1), 2);
            }
        }

        return [
            'scores' => $scores,
            'total_score' => $totalScore,
            'max_score' => 100,
            'face_match_score' => $faceMatchScore,
            'issues' => $issues,
            'details' => $details,
            'ocr_parsed' => $parsed,
            'document_face' => $documentFaceResult,
            'selfie_face' => $selfieFaceResult,
        ];
    }

    // ==========================================
    // DÉCISION
    // ==========================================

    /**
     * Prendre la décision automatique basée sur le score.
     */
    protected function makeDecision(IdentityVerification $verification, array $analysis): array
    {
        $score = $analysis['total_score'] ?? 0;
        $autoApproveThreshold = $this->config['auto_approve_threshold'] ?? 75;
        $autoRejectThreshold = $this->config['auto_reject_threshold'] ?? 20;

        $decision = 'manual_review'; // Par défaut
        $reason = '';

        if ($score >= $autoApproveThreshold && empty($analysis['issues'])) {
            // Auto-approuver : bon score et aucun problème
            $decision = 'auto_approved';
            $reason = "Score automatique: {$score}/100 — Approuvé automatiquement";

            $verification->approve(
                reviewerId: 0, // Système
                notes: $reason . "\n\nDétails: " . json_encode($analysis['scores'], JSON_PRETTY_PRINT),
            );

        } elseif ($score >= $autoApproveThreshold && !empty($analysis['issues'])) {
            // Bon score mais des problèmes → revue manuelle
            $decision = 'manual_review';
            $reason = "Score: {$score}/100 mais problèmes détectés: " . implode(', ', $analysis['issues']);

            $verification->update([
                'status' => 'manual_review',
                'admin_notes' => "🤖 KYC Auto — Score: {$score}/100\n"
                    . "Problèmes: " . implode("\n- ", $analysis['issues']),
            ]);

        } elseif ($score <= $autoRejectThreshold) {
            // Score trop bas → rejet automatique
            $decision = 'auto_rejected';
            $reason = "Score automatique: {$score}/100 — Documents insuffisants";

            $verification->reject(
                reviewerId: 0,
                reason: 'Documents illisibles ou insuffisants. Veuillez soumettre des photos plus nettes.',
                notes: "🤖 KYC Auto — Score: {$score}/100\n"
                    . "Problèmes: " . implode("\n- ", $analysis['issues']),
            );

        } else {
            // Score intermédiaire → revue manuelle
            $decision = 'manual_review';
            $reason = "Score: {$score}/100 — Revue manuelle requise";

            $issuesSummary = !empty($analysis['issues'])
                ? "\nProblèmes: " . implode("\n- ", $analysis['issues'])
                : '';

            $verification->update([
                'status' => 'manual_review',
                'admin_notes' => "🤖 KYC Auto — Score: {$score}/100{$issuesSummary}\n"
                    . "Détails OCR: " . json_encode($analysis['scores'], JSON_PRETTY_PRINT),
            ]);
        }

        Log::info('AutoKYC: Décision prise', [
            'verification_id' => $verification->id,
            'user_id' => $verification->user_id,
            'decision' => $decision,
            'score' => $score,
            'issues_count' => count($analysis['issues'] ?? []),
        ]);

        return [
            'decision' => $decision,
            'reason' => $reason,
            'score' => $score,
        ];
    }

    /**
     * Sauvegarder les résultats d'analyse dans la vérification.
     */
    protected function saveResults(IdentityVerification $verification, array $analysis): void
    {
        $extracted = [
            'auto_kyc' => true,
            'processed_at' => now()->toISOString(),
            'api' => 'google_cloud_vision',
            'scores' => $analysis['scores'] ?? [],
            'total_score' => $analysis['total_score'] ?? 0,
            'issues' => $analysis['issues'] ?? [],
            'ocr_parsed' => $analysis['ocr_parsed'] ?? [],
            'details' => $analysis['details'] ?? [],
        ];

        $updateData = [
            'extracted_data' => $extracted,
            'face_match_score' => $analysis['face_match_score'] ?? 0,
            'face_match_passed' => ($analysis['face_match_score'] ?? 0) >= ($this->config['face_match_threshold'] ?? 0.6),
        ];

        // Extraire nom/prénom si disponible
        $names = $analysis['ocr_parsed']['names'] ?? [];
        if (count($names) >= 2) {
            $updateData['last_name'] = $names[0]; // Nom
            $updateData['first_name'] = $names[1]; // Prénom
        } elseif (count($names) === 1) {
            $updateData['last_name'] = $names[0];
        }

        // Date de naissance
        if (!empty($analysis['ocr_parsed']['birth_date'])) {
            $updateData['birth_date'] = $analysis['ocr_parsed']['birth_date'];
        }

        $verification->update($updateData);
    }

    // ==========================================
    // API GOOGLE CLOUD VISION
    // ==========================================

    /**
     * Appeler l'API Google Cloud Vision.
     */
    protected function callVisionApi(array $requests): ?array
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

            Log::error('AutoKYC: Erreur API Cloud Vision', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('AutoKYC: Exception appel API', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Lire le contenu d'une image stockée.
     */
    protected function getImageContent(string $path): ?string
    {
        // Essayer d'abord le disque 'private', puis 'local'
        foreach (['private', 'local'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->get($path);
            }
        }

        // Essayer le chemin direct dans storage
        $fullPath = storage_path('app/' . $path);
        if (file_exists($fullPath)) {
            return file_get_contents($fullPath);
        }

        Log::warning('AutoKYC: Image introuvable sur tous les disques', ['path' => $path]);
        return null;
    }

    // ==========================================
    // FALLBACK
    // ==========================================

    /**
     * Basculer en revue manuelle.
     */
    protected function fallbackToManualReview(IdentityVerification $verification, string $reason): array
    {
        $verification->update([
            'status' => 'manual_review',
            'admin_notes' => "🤖 KYC Auto — Fallback manuelle: {$reason}",
        ]);

        Log::info('AutoKYC: Fallback revue manuelle', [
            'verification_id' => $verification->id,
            'reason' => $reason,
        ]);

        return [
            'decision' => 'manual_review',
            'reason' => $reason,
            'score' => 0,
        ];
    }
}
