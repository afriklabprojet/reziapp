<?php

namespace App\Services;

use App\Models\IdentityVerification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Service de validation des documents d'identité.
 *
 * Vérifie que les fichiers uploadés sont de vrais documents :
 * - Format du numéro de document (CNI CI, Passeport CEDEAO)
 * - Qualité de l'image (résolution, dimensions, ratio)
 * - Détection de doublons (même numéro pour un autre utilisateur)
 * - Vérification de la date d'expiration
 *
 * Note : Ce service fait des vérifications côté serveur sans API externe.
 * Pour une vérification OCR/IA, intégrer Onfido, Jumio ou AWS Textract.
 */
class DocumentValidationService
{
    /**
     * Résultat de validation
     */
    protected array $errors = [];

    protected array $warnings = [];

    // ==========================================
    // VALIDATION PRINCIPALE
    // ==========================================

    /**
     * Valider un document complet (image + numéro + type + date)
     */
    public function validate(
        string $documentType,
        string $documentNumber,
        string $expiryDate,
        UploadedFile $frontImage,
        ?UploadedFile $backImage = null,
        ?int $userId = null,
    ): DocumentValidationResult {
        $this->errors = [];
        $this->warnings = [];

        // 1. Valider le format du numéro
        $this->validateDocumentNumber($documentType, $documentNumber);

        // 2. Valider la date d'expiration
        $this->validateExpiryDate($expiryDate, $documentType);

        // 3. Valider l'image recto
        $this->validateDocumentImage($frontImage, 'recto');

        // 4. Valider l'image verso (obligatoire pour CNI)
        if ($documentType === 'cni') {
            if (! $backImage) {
                $this->errors[] = 'Le verso de la CNI est obligatoire.';
            } else {
                $this->validateDocumentImage($backImage, 'verso');
            }
        }

        // 5. Détecter les doublons
        if ($userId) {
            $this->detectDuplicateDocument($documentNumber, $documentType, $userId);
        }

        // 6. Cohérence type/numéro
        $this->validateTypeConsistency($documentType, $documentNumber);

        $passed = empty($this->errors);

        if (! $passed) {
            Log::warning('[KYC] Document validation failed', [
                'user_id' => $userId,
                'document_type' => $documentType,
                'errors' => $this->errors,
            ]);
        }

        return new DocumentValidationResult(
            passed: $passed,
            errors: $this->errors,
            warnings: $this->warnings,
        );
    }

    // ==========================================
    // VALIDATION DU NUMÉRO DE DOCUMENT
    // ==========================================

    /**
     * Valider le format du numéro selon le type de document.
     *
     * CNI Côte d'Ivoire :
     *   - Ancienne : C + 9 chiffres (ex: C123456789)
     *   - Nouvelle (biométrique) : CI + 10 chiffres (ex: CI0012345678)
     *   - Accepte aussi : séquence de 9 à 13 chiffres
     *
     * Passeport Côte d'Ivoire (CEDEAO) :
     *   - Format : 2 lettres + 7 chiffres (ex: PB1234567)
     *   - Ou : lettre + 8 chiffres
     */
    public function validateDocumentNumber(string $type, string $number): bool
    {
        // Nettoyer le numéro (supprimer espaces, tirets)
        $cleanNumber = preg_replace('/[\s\-.]/', '', trim($number));

        if (empty($cleanNumber)) {
            $this->errors[] = 'Le numéro de document est requis.';

            return false;
        }

        return match ($type) {
            'cni' => $this->validateCniNumber($cleanNumber),
            'passport' => $this->validatePassportNumber($cleanNumber),
            default => $this->addError("Type de document non reconnu : {$type}"),
        };
    }

    /**
     * Valider un numéro de CNI ivoirienne.
     */
    protected function validateCniNumber(string $number): bool
    {
        // Patterns acceptés pour la CNI de Côte d'Ivoire
        $patterns = [
            '/^C\d{9}$/i',           // Ancienne CNI : C + 9 chiffres
            '/^CI\d{10}$/i',         // Nouvelle biométrique : CI + 10 chiffres
            '/^CI\d{9}$/i',          // Variante : CI + 9 chiffres
            '/^\d{9,13}$/',          // Séquence numérique pure (9-13 chiffres)
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $number)) {
                return true;
            }
        }

        $this->errors[] = 'Le numéro de CNI n\'est pas au bon format. '
            .'Formats acceptés : C123456789, CI0012345678 ou une séquence de 9 à 13 chiffres.';

        return false;
    }

    /**
     * Valider un numéro de passeport ivoirien / CEDEAO.
     */
    protected function validatePassportNumber(string $number): bool
    {
        // Patterns acceptés pour le passeport ivoirien / CEDEAO
        $patterns = [
            '/^\d{2}[A-Z]{2}\d{5}$/i',  // Format CI courant : 2 chiffres + 2 lettres + 5 chiffres (18AV13696)
            '/^[A-Z]{2}\d{7}$/i',        // Standard CEDEAO : 2 lettres + 7 chiffres (PB1234567)
            '/^[A-Z]\d{8}$/i',           // Variante : 1 lettre + 8 chiffres
            '/^[A-Z]{1,3}\d{6,9}$/i',    // Format élargi pour autres pays CEDEAO
            '/^\d{2}[A-Z]{1,2}\d{4,6}$/i', // Variantes numériques CI (ex: 20AB12345)
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $number)) {
                return true;
            }
        }

        $this->errors[] = 'Le numéro de passeport n\'est pas au bon format. '
            .'Formats acceptés : 18AV13696, PB1234567, etc.';

        return false;
    }

    // ==========================================
    // VALIDATION DE L'IMAGE
    // ==========================================

    /**
     * Valider qu'une image ressemble à un document d'identité.
     */
    public function validateDocumentImage(UploadedFile $file, string $label = 'document'): bool
    {
        $valid = true;

        // 1. Vérifier le type MIME réel (pas juste l'extension)
        $realMimeType = $file->getMimeType();
        $allowedMimes = config('rezi.kyc.identity.allowed_mime_types', [
            'image/jpeg', 'image/png', 'image/webp',
        ]);

        if (! in_array($realMimeType, $allowedMimes)) {
            $this->errors[] = "Le fichier ({$label}) doit être une image JPEG, PNG ou WebP. Type détecté : {$realMimeType}.";

            return false;
        }

        // 2. Vérifier la taille du fichier
        $maxSizeKb = config('rezi.kyc.identity.max_file_size_kb', 10240);
        $fileSizeKb = $file->getSize() / 1024;

        if ($fileSizeKb > $maxSizeKb) {
            $this->errors[] = "L'image ({$label}) est trop volumineuse ({$this->formatSize($fileSizeKb)}). Maximum : {$this->formatSize($maxSizeKb)}.";
            $valid = false;
        }

        // 3. Taille minimum (un document scanné fait au moins 50 Ko)
        if ($fileSizeKb < 50) {
            $this->errors[] = "L'image ({$label}) est trop petite ({$this->formatSize($fileSizeKb)}). Elle ne ressemble pas à une photo de document.";
            $valid = false;
        }

        // 4. Vérifier les dimensions de l'image
        $imageInfo = @getimagesize($file->path());

        if ($imageInfo === false) {
            $this->errors[] = "Impossible de lire l'image ({$label}). Le fichier est peut-être corrompu.";

            return false;
        }

        [$width, $height] = $imageInfo;

        // Résolution minimum : 640x400 (un document doit être lisible)
        $minWidth = config('rezi.kyc.identity.min_image_width', 640);
        $minHeight = config('rezi.kyc.identity.min_image_height', 400);

        if ($width < $minWidth || $height < $minHeight) {
            $this->errors[] = "L'image ({$label}) est trop basse résolution ({$width}×{$height}px). Minimum requis : {$minWidth}×{$minHeight}px.";
            $valid = false;
        }

        // 5. Vérifier l'aspect ratio (un document est rectangulaire, pas carré)
        $this->validateDocumentAspectRatio($width, $height, $label);

        // 6. Résolution max (pas besoin de 8000x6000, signe de fichier suspect)
        if ($width > 6000 || $height > 6000) {
            $this->warnings[] = "L'image ({$label}) a une résolution très élevée ({$width}×{$height}px). Elle sera optimisée.";
        }

        return $valid;
    }

    /**
     * Vérifier que le ratio largeur/hauteur est cohérent avec un document d'identité.
     *
     * CNI standard : environ 85.6mm × 54mm = ratio ~1.58 (format carte bancaire ISO/IEC 7810)
     * Passeport ouvert : environ 125mm × 88mm = ratio ~1.42
     * On accepte un ratio entre 1.2 et 2.0 (tolérance pour photo en angle, etc.)
     * On accepte aussi l'inverse (photo en portrait) entre 0.5 et 0.83
     */
    protected function validateDocumentAspectRatio(int $width, int $height, string $label): void
    {
        if ($height === 0) {
            return;
        }

        $ratio = $width / $height;

        // Ratio document paysage : 1.2 à 2.0
        // Ratio document portrait : 0.5 à 0.83 (l'inverse)
        $isLandscape = $ratio >= 1.1 && $ratio <= 2.2;
        $isPortrait = $ratio >= 0.45 && $ratio <= 0.91;

        if (! $isLandscape && ! $isPortrait) {
            // C'est probablement un selfie ou une image carrée
            $this->warnings[] = "L'image ({$label}) n'a pas les proportions typiques d'un document d'identité (ratio {$this->round($ratio)}). "
                .'Assurez-vous de photographier le document entier.';
        }
    }

    // ==========================================
    // DÉTECTION DE DOUBLONS
    // ==========================================

    /**
     * Vérifier si le même numéro de document a déjà été utilisé par un autre utilisateur.
     */
    public function detectDuplicateDocument(string $documentNumber, string $documentType, int $userId): bool
    {
        $cleanNumber = preg_replace('/[\s\-.]/', '', trim($documentNumber));

        $duplicate = IdentityVerification::where('document_number', $cleanNumber)
            ->where('user_id', '!=', $userId)
            ->whereIn('status', ['submitted', 'processing', 'manual_review', 'approved'])
            ->exists();

        if ($duplicate) {
            $this->errors[] = 'Ce numéro de document est déjà associé à un autre compte. '
                .'Si vous pensez qu\'il s\'agit d\'une erreur, contactez le support.';

            Log::warning('[KYC] Duplicate document detected', [
                'document_type' => $documentType,
                'user_id' => $userId,
                // Ne pas logger le numéro complet pour la sécurité
                'document_prefix' => substr($cleanNumber, 0, 3).'***',
            ]);

            return true;
        }

        return false;
    }

    // ==========================================
    // VALIDATION DE LA DATE D'EXPIRATION
    // ==========================================

    /**
     * Valider la date d'expiration du document.
     */
    public function validateExpiryDate(string $expiryDate, string $documentType): bool
    {
        try {
            $expiry = \Carbon\Carbon::parse($expiryDate);
        } catch (\Exception $e) {
            $this->errors[] = 'La date d\'expiration n\'est pas valide.';

            return false;
        }

        // Le document ne doit pas être expiré
        if ($expiry->isPast()) {
            $this->errors[] = 'Le document est expiré. Veuillez fournir un document en cours de validité.';

            return false;
        }

        // Le document ne doit pas expirer dans les 30 prochains jours
        if ($expiry->isBefore(now()->addDays(30))) {
            $this->warnings[] = 'Votre document expire bientôt (le '.$expiry->format('d/m/Y').'). '
                .'Nous vous recommandons de fournir un document avec une validité plus longue.';
        }

        // La date ne devrait pas être dans plus de 15 ans (durée max d'un passeport)
        $maxYears = $documentType === 'passport' ? 15 : 12;
        if ($expiry->isAfter(now()->addYears($maxYears))) {
            $this->errors[] = "La date d'expiration semble incorrecte (plus de {$maxYears} ans). Vérifiez la date saisie.";

            return false;
        }

        return true;
    }

    // ==========================================
    // COHÉRENCE TYPE / NUMÉRO
    // ==========================================

    /**
     * Vérifier la cohérence entre le type de document et le format du numéro.
     */
    protected function validateTypeConsistency(string $type, string $number): void
    {
        $cleanNumber = preg_replace('/[\s\-.]/', '', trim($number));

        // Si type = CNI mais le numéro ressemble à un passeport (2 lettres + chiffres)
        if ($type === 'cni' && preg_match('/^[A-Z]{2}\d{7}$/i', $cleanNumber)) {
            if (! preg_match('/^CI/i', $cleanNumber)) {
                $this->warnings[] = 'Le numéro ressemble à un format de passeport. '
                    .'Êtes-vous sûr d\'avoir sélectionné le bon type de document ?';
            }
        }

        // Si type = passeport mais le numéro ressemble à une CNI (C + chiffres ou tout chiffres, sans lettres au milieu)
        if ($type === 'passport' && (preg_match('/^C\d{9}$/i', $cleanNumber) || preg_match('/^\d{9,13}$/', $cleanNumber))) {
            $this->warnings[] = 'Le numéro ressemble à un format de CNI. '
                .'Êtes-vous sûr d\'avoir sélectionné le bon type de document ?';
        }
    }

    // ==========================================
    // HELPERS
    // ==========================================

    protected function addError(string $message): bool
    {
        $this->errors[] = $message;

        return false;
    }

    protected function formatSize(float $kb): string
    {
        if ($kb >= 1024) {
            return round($kb / 1024, 1).' Mo';
        }

        return round($kb).' Ko';
    }

    protected function round(float $value): string
    {
        return number_format($value, 2);
    }
}

/**
 * Résultat de la validation d'un document.
 */
class DocumentValidationResult
{
    public function __construct(
        public readonly bool $passed,
        public readonly array $errors = [],
        public readonly array $warnings = [],
    ) {
    }

    public function failed(): bool
    {
        return ! $this->passed;
    }

    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }

    /**
     * Obtenir tous les messages d'erreur formatés pour un MessageBag Laravel.
     */
    public function errorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $i => $error) {
            $messages["document_validation.{$i}"] = $error;
        }

        return $messages;
    }
}
