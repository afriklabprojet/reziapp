<?php

namespace App\Services;

use App\Jobs\ProcessAutoKyc;
use App\Models\Blacklist;
use App\Models\EmergencyAlert;
use App\Models\EmergencyContact;
use App\Models\FraudReport;
use App\Models\IdentityVerification;
use App\Models\PhoneVerification;
use App\Models\Residence;
use App\Models\ResidenceVerification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VerificationService
{
    // ==========================================
    // VÉRIFICATION D'IDENTITÉ
    // ==========================================

    /**
     * Initier une vérification d'identité
     */
    public function initiateIdentityVerification(User $user, string $documentType = 'cni'): IdentityVerification
    {
        // Vérifier s'il y a déjà une vérification en cours
        $existing = IdentityVerification::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'submitted', 'processing'])
            ->first();

        if ($existing) {
            return $existing;
        }

        // Vérifier si une vérification approuvée existe et est encore valide
        $approved = IdentityVerification::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($approved) {
            return $approved;
        }

        return IdentityVerification::create([
            'user_id' => $user->id,
            'document_type' => $documentType,
            'status' => 'pending',
        ]);
    }

    /**
     * Soumettre les documents d'identité
     */
    public function submitIdentityDocuments(
        IdentityVerification $verification,
        UploadedFile $documentFront,
        UploadedFile $selfie,
        ?UploadedFile $documentBack = null,
        ?string $documentNumber = null,
    ): IdentityVerification {
        // Stocker les fichiers
        $frontPath = $this->storeSecureFile($documentFront, 'identity', $verification->user_id);
        $backPath = $documentBack ? $this->storeSecureFile($documentBack, 'identity', $verification->user_id) : null;
        $selfiePath = $this->storeSecureFile($selfie, 'selfies', $verification->user_id);

        $verification->update([
            'document_front' => $frontPath,
            'document_back' => $backPath,
            'selfie_photo' => $selfiePath,
            'document_number' => $documentNumber ? encrypt($documentNumber) : null,
            'status' => 'submitted',
            'attempt_count' => $verification->attempt_count + 1,
            'last_attempt_at' => now(),
        ]);

        // Lancer la vérification automatique (si API disponible)
        $this->processAutomaticVerification($verification);

        return $verification;
    }

    /**
     * Stocker un fichier de manière sécurisée
     */
    protected function storeSecureFile(UploadedFile $file, string $folder, int $userId): string
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = "private/verifications/{$folder}/{$userId}/{$filename}";

        Storage::disk('local')->put($path, file_get_contents($file->path()));

        return $path;
    }

    /**
     * Traitement automatique de la vérification via Google Cloud Vision API.
     *
     * Si l'auto-KYC est activé (GOOGLE_CLOUD_VISION_API_KEY + config rezi.kyc.auto_verification.enabled),
     * le job ProcessAutoKyc est dispatché pour :
     *   - OCR du document (extraction texte, numéro, nom, dates)
     *   - Détection de visage (document + selfie)
     *   - Scoring automatique et décision (auto-approve / manual_review / auto-reject)
     *
     * Si l'auto-KYC n'est pas activé, la vérification reste en manual_review
     * et un admin approuve/rejette via Filament (/admin/identity-verifications).
     */
    public function processAutomaticVerification(IdentityVerification $verification): void
    {
        $autoKycEnabled = config('rezi.kyc.auto_verification.enabled', false)
            && !empty(config('services.google_cloud_vision.api_key'));

        if ($autoKycEnabled) {
            // Dispatche le job de vérification automatique
            ProcessAutoKyc::dispatch($verification);

            Log::info('IdentityVerification: Auto-KYC job dispatché', [
                'verification_id' => $verification->id,
                'user_id' => $verification->user_id,
                'document_type' => $verification->document_type,
            ]);
        } else {
            // Fallback : revue manuelle par admin
            $verification->update(['status' => 'manual_review']);

            Log::info('IdentityVerification: Passage en revue manuelle (auto-KYC désactivé)', [
                'verification_id' => $verification->id,
                'user_id' => $verification->user_id,
                'document_type' => $verification->document_type,
            ]);
        }
    }

    /**
     * Correspondance faciale.
     *
     * Quand l'auto-KYC est actif, le score est calculé et stocké par AutoKycService.
     * Cette méthode retourne le score déjà enregistré, ou 0.0 si pas encore traité.
     */
    public function verifyFaceMatch(IdentityVerification $verification): float
    {
        return (float) ($verification->face_match_score ?? 0.0);
    }

    // ==========================================
    // VÉRIFICATION TÉLÉPHONE
    // ==========================================

    /**
     * Initier une vérification de téléphone
     */
    public function initiatePhoneVerification(User $user, string $phone, string $countryCode = '+225'): PhoneVerification
    {
        // Nettoyer le numéro
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Vérifier si déjà vérifié
        $existing = PhoneVerification::where('user_id', $user->id)
            ->where('phone', $phone)
            ->where('status', 'verified')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Créer ou récupérer la vérification en cours
        $verification = PhoneVerification::firstOrCreate(
            [
                'user_id' => $user->id,
                'phone' => $phone,
                'status' => 'pending',
            ],
            [
                'country_code' => $countryCode,
            ],
        );

        return $verification;
    }

    /**
     * Envoyer le code OTP
     */
    public function sendOtp(PhoneVerification $verification): bool
    {
        if (!$verification->canResend()) {
            return false;
        }

        $code = $verification->generateOtp();
        $phone = $verification->getFullPhone();
        $message = "Votre code Rezi Studio Meublé Faya : {$code}. Valable 10 minutes. Ne partagez ce code avec personne.";

        try {
            app(SmsService::class)->send($phone, $message);
        } catch (\Throwable $e) {
            Log::warning('[KYC SMS] Failed to send OTP', [
                'phone' => substr($phone, 0, -4).'****',
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    /**
     * Vérifier le code OTP
     */
    public function verifyOtp(PhoneVerification $verification, string $code): bool
    {
        return $verification->verifyOtp($code);
    }

    // ==========================================
    // VÉRIFICATION DE RÉSIDENCE
    // ==========================================

    /**
     * Initier une vérification de résidence
     */
    public function initiateResidenceVerification(
        Residence $residence,
        string $verificationType = 'document',
    ): ResidenceVerification {
        // Vérifier s'il y a déjà une vérification en cours
        $existing = ResidenceVerification::where('residence_id', $residence->id)
            ->whereIn('status', ['pending', 'documents_submitted', 'visit_scheduled', 'under_review'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return ResidenceVerification::create([
            'residence_id' => $residence->id,
            'user_id' => $residence->owner_id,
            'verification_type' => $verificationType,
            'status' => 'pending',
        ]);
    }

    /**
     * Soumettre les documents de résidence
     */
    public function submitResidenceDocuments(
        ResidenceVerification $verification,
        UploadedFile $document,
        string $documentType,
    ): ResidenceVerification {
        $path = $this->storeSecureFile($document, 'residences', $verification->user_id);

        $verification->update([
            'proof_document' => $path,
            'document_type' => $documentType,
            'status' => 'documents_submitted',
        ]);

        return $verification;
    }

    /**
     * Vérification GPS de la résidence
     */
    public function verifyResidenceGps(
        ResidenceVerification $verification,
        float $lat,
        float $lng,
        float $accuracy,
    ): bool {
        return $verification->verifyGps($lat, $lng, $accuracy);
    }

    // ==========================================
    // DÉTECTION DE FRAUDE
    // ==========================================

    /**
     * Signaler une fraude
     */
    public function reportFraud(
        string $targetType,
        int $targetId,
        string $fraudType,
        string $description,
        ?User $reporter = null,
        ?array $evidence = null,
    ): FraudReport {
        // Trouver l'utilisateur cible
        $targetUserId = $this->getTargetUserId($targetType, $targetId);

        $report = FraudReport::create([
            'reporter_id' => $reporter?->id,
            'reporter_ip' => request()->ip(),
            'reporter_user_agent' => request()->userAgent(),
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_user_id' => $targetUserId,
            'fraud_type' => $fraudType,
            'description' => $description,
            'evidence' => $evidence,
            'status' => 'pending',
        ]);

        // Calculer le score de risque
        $report->calculateRiskScore();

        // Alerter si critique
        if ($report->priority === 'critical') {
            $this->alertCriticalFraud($report);
        }

        return $report;
    }

    /**
     * Obtenir l'ID utilisateur de la cible
     */
    protected function getTargetUserId(string $type, int $id): ?int
    {
        return match($type) {
            'user' => $id,
            'residence' => Residence::find($id)?->owner_id,
            'review' => \App\Models\Review::find($id)?->user_id,
            'message' => \App\Models\Message::find($id)?->sender_id,
            'contact' => \App\Models\Contact::find($id)?->user_id,
            default => null,
        };
    }

    /**
     * Alerter pour fraude critique
     */
    protected function alertCriticalFraud(FraudReport $report): void
    {
        Log::alert('Critical fraud detected', [
            'report_id' => $report->id,
            'fraud_type' => $report->fraud_type,
            'risk_score' => $report->risk_score,
        ]);

        // Notifier tous les admins par email
        $admins = User::where('role', 'admin')->get();
        \Illuminate\Support\Facades\Notification::send(
            $admins,
            new \App\Notifications\CriticalFraudAlert($report),
        );

        // Notification in-app pour chaque admin
        foreach ($admins as $admin) {
            \App\Models\Notification::send(
                $admin,
                'system',
                '🚨 Fraude critique détectée',
                "Signalement #{$report->id} — Type: {$report->fraud_type}, Score: {$report->risk_score}/100",
                url('/admin/fraud-reports/'.$report->id),
                ['fraud_report_id' => $report->id, 'risk_score' => $report->risk_score],
            );
        }
    }

    /**
     * Analyser un utilisateur pour détection de fraude
     */
    public function analyzeUserForFraud(User $user): array
    {
        $risks = [];
        $score = 0;

        // Vérifier le compte récent avec beaucoup d'activité
        if ($user->created_at->isAfter(now()->subDays(7))) {
            $residencesCount = $user->residences()->count();
            if ($residencesCount > 3) {
                $risks[] = "Compte récent avec {$residencesCount} annonces";
                $score += 20;
            }
        }

        // Vérifier les signalements précédents
        $previousReports = FraudReport::where('target_user_id', $user->id)
            ->where('status', 'confirmed')
            ->count();

        if ($previousReports > 0) {
            $risks[] = "Fraudes confirmées précédemment: {$previousReports}";
            $score += $previousReports * 25;
        }

        // Vérifier l'identité non vérifiée
        if (!$user->identity_verified) {
            $risks[] = 'Identité non vérifiée';
            $score += 10;
        }

        // Vérifier le téléphone non vérifié
        if (!$user->phone_verified) {
            $risks[] = 'Téléphone non vérifié';
            $score += 10;
        }

        // Prix anormalement bas
        $avgPrice = $user->residences()->avg('price_per_day');
        $marketAvg = Residence::approved()->avg('price_per_day');
        if ($avgPrice && $marketAvg && $avgPrice < ($marketAvg * 0.3)) {
            $risks[] = 'Prix 70% en dessous du marché';
            $score += 30;
        }

        return [
            'user_id' => $user->id,
            'risk_score' => min($score, 100),
            'risk_factors' => $risks,
            'recommendation' => $score >= 50 ? 'review_required' : ($score >= 30 ? 'monitor' : 'ok'),
        ];
    }

    // ==========================================
    // BLACKLIST
    // ==========================================

    /**
     * Vérifier si un utilisateur peut s'inscrire (pas blacklisté)
     */
    public function canRegister(string $email, ?string $phone = null): array
    {
        $issues = [];

        // Vérifier l'email
        if (Blacklist::isBlacklisted('email', $email)) {
            $issues[] = 'email_blacklisted';
        }

        // Vérifier le téléphone
        if ($phone && Blacklist::isBlacklisted('phone', $phone)) {
            $issues[] = 'phone_blacklisted';
        }

        // Vérifier l'IP
        $ip = request()->ip();
        if (Blacklist::isBlacklisted('ip', $ip)) {
            $issues[] = 'ip_blacklisted';
        }

        return [
            'can_register' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Blacklister un utilisateur
     */
    public function blacklistUser(
        User $user,
        string $reason,
        string $description,
        int $adminId,
        string $restrictionLevel = 'banned',
        bool $permanent = true,
        ?\DateTime $expiresAt = null,
    ): Blacklist {
        // Suspendre l'utilisateur (forceFill car is_suspended est protégé dans $fillable)
        $user->forceFill([
            'is_suspended' => true,
            'suspended_until' => $permanent ? null : $expiresAt,
            'suspension_reason' => $description,
        ])->save();

        // Désactiver ses annonces
        $user->residences()->update(['status' => 'suspended']);

        // Créer l'entrée blacklist
        return Blacklist::blacklistUser(
            $user,
            $reason,
            $restrictionLevel,
            $description,
            $adminId,
            $permanent,
            $expiresAt,
        );
    }

    /**
     * Lever le blacklist d'un utilisateur
     */
    public function unblacklistUser(User $user, int $adminId): void
    {
        // Désactiver toutes les entrées blacklist
        Blacklist::where('user_id', $user->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_by' => $adminId,
            ]);

        // Réactiver l'utilisateur
        $user->update([
            'is_suspended' => false,
            'suspended_until' => null,
            'suspension_reason' => null,
        ]);
    }

    // ==========================================
    // MODE URGENCE
    // ==========================================

    /**
     * Déclencher une alerte d'urgence
     */
    public function triggerEmergency(
        User $user,
        string $type = 'panic',
        ?string $message = null,
        ?float $lat = null,
        ?float $lng = null,
        ?array $context = null,
    ): EmergencyAlert {
        $alert = EmergencyAlert::trigger($user, $type, $message, $lat, $lng, $context);

        // Notifier immédiatement les contacts
        $alert->notifyContacts();

        // Notifier les admins si SOS ou panique
        if (in_array($type, ['panic', 'sos'])) {
            $this->notifyAdminsOfEmergency($alert);
        }

        return $alert;
    }

    /**
     * Notifier les admins d'une urgence
     */
    protected function notifyAdminsOfEmergency(EmergencyAlert $alert): void
    {
        $admins = User::where('role', 'admin')->get();

        \Illuminate\Support\Facades\Notification::send(
            $admins,
            new \App\Notifications\EmergencyAlertTriggered($alert),
        );

        foreach ($admins as $admin) {
            \App\Models\Notification::send(
                $admin,
                'system',
                '🆘 Alerte urgence déclenchée',
                "L'utilisateur {$alert->user->name} a déclenché une alerte {$alert->alert_type}.",
                route('filament.admin.resources.emergency-alerts.index'),
                ['alert_id' => $alert->id, 'type' => $alert->alert_type],
            );
        }

        Log::alert('Emergency alert triggered', [
            'alert_id' => $alert->id,
            'user_id' => $alert->user_id,
            'type' => $alert->alert_type,
            'location' => [$alert->latitude, $alert->longitude],
        ]);
    }

    /**
     * Ajouter un contact d'urgence
     */
    public function addEmergencyContact(
        User $user,
        string $name,
        string $phone,
        ?string $relationship = null,
        bool $primary = false,
    ): EmergencyContact {
        $contact = EmergencyContact::create([
            'user_id' => $user->id,
            'name' => $name,
            'phone' => preg_replace('/[^0-9+]/', '', $phone),
            'relationship' => $relationship,
            'is_primary' => false,
            'notify_on_emergency' => true,
        ]);

        if ($primary) {
            $contact->setAsPrimary();
        }

        return $contact;
    }

    // ==========================================
    // NIVEAU DE VÉRIFICATION
    // ==========================================

    /**
     * Calculer le niveau de vérification d'un utilisateur
     */
    /**
     * Calculer le score de confiance numérique (0-100)
     */
    public function calculateTrustScore(User $user): int
    {
        $points = 0;
        $config = config('rezi.kyc.trust_points', []);

        // Email vérifié
        if ($user->email_verified_at || $user->email_verified) {
            $points += $config['email_verified'] ?? 10;
        }

        // Téléphone vérifié
        if ($user->phone_verified) {
            $points += $config['phone_verified'] ?? 20;
        }

        // Identité vérifiée
        if ($user->identity_verified) {
            $points += $config['identity_verified'] ?? 40;
        }

        // Photo de profil
        if ($user->profile_photo || $user->avatar) {
            $points += $config['profile_photo'] ?? 5;
        }

        // Compte ancien (>6 mois)
        if ($user->created_at && $user->created_at->isBefore(now()->subMonths(6))) {
            $points += $config['account_age_6m'] ?? 10;
        }

        // Avis positifs
        if ($user->reviews()->where('rating', '>=', 4)->count() >= 3) {
            $points += $config['positive_reviews_3'] ?? 15;
        }

        // Double authentification activée
        if ($user->two_factor_enabled) {
            $points += $config['two_factor_enabled'] ?? 10;
        }

        return min($points, 100);
    }

    public function calculateVerificationLevel(User $user): string
    {
        $points = $this->calculateTrustScore($user);
        $levels = config('rezi.kyc.trust_levels', [
            'none' => 0, 'basic' => 20, 'standard' => 40, 'premium' => 60, 'trusted' => 80,
        ]);

        return match (true) {
            $points >= $levels['trusted'] => 'trusted',
            $points >= $levels['premium'] => 'premium',
            $points >= $levels['standard'] => 'standard',
            $points >= $levels['basic'] => 'basic',
            default => 'none',
        };
    }

    /**
     * Mettre à jour le niveau de vérification
     */
    public function updateVerificationLevel(User $user): void
    {
        $level = $this->calculateVerificationLevel($user);
        $user->update(['verification_level' => $level]);
    }

    // ==========================================
    // STATISTIQUES
    // ==========================================

    /**
     * Obtenir les statistiques de vérification
     */
    public function getVerificationStats(): array
    {
        return [
            'identity' => [
                'pending' => IdentityVerification::pending()->count(),
                'needs_review' => IdentityVerification::needsReview()->count(),
                'approved' => IdentityVerification::approved()->count(),
                'total' => IdentityVerification::count(),
            ],
            'phone' => [
                'verified' => PhoneVerification::verified()->count(),
                'pending' => PhoneVerification::pending()->count(),
            ],
            'residence' => [
                'pending' => ResidenceVerification::pending()->count(),
                'approved' => ResidenceVerification::approved()->count(),
            ],
            'fraud' => [
                'pending' => FraudReport::pending()->count(),
                'high_priority' => FraudReport::highPriority()->pending()->count(),
                'confirmed' => FraudReport::confirmed()->count(),
            ],
            'blacklist' => [
                'active' => Blacklist::active()->count(),
                'banned' => Blacklist::active()->banned()->count(),
            ],
            'emergency' => [
                'active' => EmergencyAlert::active()->count(),
                'today' => EmergencyAlert::whereDate('created_at', today())->count(),
            ],
        ];
    }
}
