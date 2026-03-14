<?php

namespace App\Services;

use App\Models\User;
use App\Models\Residence;
use App\Models\Booking;
use App\Models\FraudReport;
use App\Models\MarketPriceData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecurityService
{
    // Niveaux de risque
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    // Indicateurs de fraude
    const FLAG_NEW_ACCOUNT = 'new_account';
    const FLAG_UNVERIFIED_PHONE = 'unverified_phone';
    const FLAG_UNVERIFIED_EMAIL = 'unverified_email';
    const FLAG_SUSPICIOUS_PRICE = 'suspicious_price';
    const FLAG_DUPLICATE_PHOTOS = 'duplicate_photos';
    const FLAG_REPORTED_USER = 'reported_user';
    const FLAG_IP_FLAGGED = 'ip_flagged';
    const FLAG_MULTIPLE_ACCOUNTS = 'multiple_accounts';

    /**
     * Analyser le score de confiance d'un utilisateur
     */
    public function analyzeTrustScore(User $user): array
    {
        $score = 100;
        $flags = [];
        $positives = [];

        // Facteurs négatifs
        
        // Compte récent (moins de 7 jours)
        if ($user->created_at->diffInDays(now()) < 7) {
            $score -= 15;
            $flags[] = [
                'type' => self::FLAG_NEW_ACCOUNT,
                'severity' => 'medium',
                'message' => 'Compte créé récemment',
            ];
        }

        // Téléphone non vérifié
        if (!$user->phone_verified) {
            $score -= 20;
            $flags[] = [
                'type' => self::FLAG_UNVERIFIED_PHONE,
                'severity' => 'high',
                'message' => 'Numéro de téléphone non vérifié',
            ];
        }

        // Email non vérifié
        if (!$user->email_verified_at) {
            $score -= 10;
            $flags[] = [
                'type' => self::FLAG_UNVERIFIED_EMAIL,
                'severity' => 'medium',
                'message' => 'Email non vérifié',
            ];
        }

        // Signalements reçus
        $reportCount = FraudReport::where('target_type', 'user')->where('target_id', $user->id)->count();
        if ($reportCount > 0) {
            $score -= min(30, $reportCount * 10);
            $flags[] = [
                'type' => self::FLAG_REPORTED_USER,
                'severity' => $reportCount > 2 ? 'critical' : 'high',
                'message' => "{$reportCount} signalement(s) reçu(s)",
            ];
        }

        // Facteurs positifs
        
        // Identité vérifiée
        if ($user->identity_verified) {
            $score += 15;
            $positives[] = 'Identité vérifiée';
        }

        // Compte ancien (plus d'un an)
        if ($user->created_at->diffInYears(now()) >= 1) {
            $score += 10;
            $positives[] = 'Membre depuis plus d\'un an';
        }

        // Historique de réservations réussies
        $successfulBookings = $user->bookings()
            ->where('status', 'completed')
            ->count();
        if ($successfulBookings >= 3) {
            $score += 10;
            $positives[] = "{$successfulBookings} réservations réussies";
        }

        // Bonne note moyenne
        $avgRating = $user->receivedReviews()->avg('rating');
        if ($avgRating >= 4.5) {
            $score += 10;
            $positives[] = "Note moyenne: {$avgRating}/5";
        }

        // Normaliser le score
        $score = max(0, min(100, $score));

        // Déterminer le niveau de risque
        $riskLevel = match (true) {
            $score >= 80 => self::RISK_LOW,
            $score >= 60 => self::RISK_MEDIUM,
            $score >= 40 => self::RISK_HIGH,
            default => self::RISK_CRITICAL,
        };

        return [
            'score' => $score,
            'risk_level' => $riskLevel,
            'flags' => $flags,
            'positives' => $positives,
            'can_book' => $score >= 30,
            'can_list' => $score >= 50,
            'requires_review' => $score < 60,
        ];
    }

    /**
     * Analyser une résidence pour détecter les fraudes
     */
    public function analyzeResidenceFraud(Residence $residence): array
    {
        $flags = [];
        $riskScore = 0;

        // Prix suspect (trop bas)
        $marketPrice = MarketPriceData::getMarketPrice(
            $residence->commune ?? $residence->city,
            $residence->type,
            $residence->bedrooms ?? 1
        );
        
        if ($marketPrice && $residence->price < $marketPrice['min'] * 0.5) {
            $riskScore += 30;
            $flags[] = [
                'type' => self::FLAG_SUSPICIOUS_PRICE,
                'severity' => 'high',
                'message' => 'Prix anormalement bas par rapport au marché',
            ];
        }

        // Propriétaire non vérifié
        $ownerTrust = $this->analyzeTrustScore($residence->user);
        if ($ownerTrust['risk_level'] === self::RISK_HIGH || $ownerTrust['risk_level'] === self::RISK_CRITICAL) {
            $riskScore += 20;
            $flags[] = [
                'type' => 'owner_risk',
                'severity' => 'high',
                'message' => 'Propriétaire avec score de confiance faible',
            ];
        }

        // Photos insuffisantes
        $photoCount = $residence->photos()->count();
        if ($photoCount < 3) {
            $riskScore += 10;
            $flags[] = [
                'type' => 'insufficient_photos',
                'severity' => 'medium',
                'message' => 'Peu de photos disponibles',
            ];
        }

        // Pas de géolocalisation
        if (!$residence->latitude || !$residence->longitude) {
            $riskScore += 15;
            $flags[] = [
                'type' => 'no_location',
                'severity' => 'medium',
                'message' => 'Localisation exacte non fournie',
            ];
        }

        // Déterminer si nécessite une vérification manuelle
        $requiresReview = $riskScore >= 30;

        return [
            'risk_score' => $riskScore,
            'risk_level' => match (true) {
                $riskScore >= 50 => self::RISK_CRITICAL,
                $riskScore >= 30 => self::RISK_HIGH,
                $riskScore >= 15 => self::RISK_MEDIUM,
                default => self::RISK_LOW,
            },
            'flags' => $flags,
            'requires_review' => $requiresReview,
            'auto_approve' => $riskScore < 15 && $ownerTrust['score'] >= 70,
        ];
    }

    /**
     * Vérifier les tentatives de connexion suspectes
     */
    public function checkSuspiciousLogin(string $ip, string $email): array
    {
        $cacheKey = "login_attempts:{$ip}";
        $attempts = Cache::get($cacheKey, 0);

        // Vérifier si l'IP est dans une liste noire
        $isBlacklisted = $this->isIpBlacklisted($ip);

        // Vérifier les tentatives récentes
        $isSuspicious = $attempts >= 5 || $isBlacklisted;

        if ($isSuspicious) {
            Log::warning('Suspicious login attempt', [
                'ip' => $ip,
                'email' => $email,
                'attempts' => $attempts,
                'blacklisted' => $isBlacklisted,
            ]);
        }

        return [
            'is_suspicious' => $isSuspicious,
            'attempts' => $attempts,
            'is_blacklisted' => $isBlacklisted,
            'require_captcha' => $attempts >= 3,
            'require_2fa' => $attempts >= 5,
        ];
    }

    /**
     * Enregistrer une tentative de connexion échouée
     */
    public function recordFailedLogin(string $ip): void
    {
        $cacheKey = "login_attempts:{$ip}";
        $attempts = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $attempts + 1, now()->addMinutes(30));
    }

    /**
     * Réinitialiser les tentatives après connexion réussie
     */
    public function clearLoginAttempts(string $ip): void
    {
        Cache::forget("login_attempts:{$ip}");
    }

    /**
     * Vérifier si une IP est dans la liste noire
     */
    protected function isIpBlacklisted(string $ip): bool
    {
        // Liste noire en cache (peut être alimentée depuis une base de données)
        $blacklist = Cache::remember('ip_blacklist', 3600, function () {
            // Ici on pourrait charger depuis la DB
            return [];
        });

        return in_array($ip, $blacklist);
    }

    /**
     * Générer des indicateurs de confiance pour l'affichage
     */
    public function getTrustIndicators(User $user): array
    {
        $indicators = [];

        // Badge identité
        if ($user->identity_verified) {
            $indicators[] = [
                'icon' => 'heroicon-o-identification',
                'label' => 'Identité vérifiée',
                'color' => 'green',
                'verified' => true,
            ];
        }

        // Badge téléphone
        if ($user->phone_verified) {
            $indicators[] = [
                'icon' => 'heroicon-o-phone',
                'label' => 'Téléphone vérifié',
                'color' => 'green',
                'verified' => true,
            ];
        } else {
            $indicators[] = [
                'icon' => 'heroicon-o-phone',
                'label' => 'Téléphone non vérifié',
                'color' => 'gray',
                'verified' => false,
            ];
        }

        // Badge email
        if ($user->email_verified_at) {
            $indicators[] = [
                'icon' => 'heroicon-o-envelope',
                'label' => 'Email vérifié',
                'color' => 'green',
                'verified' => true,
            ];
        }

        // Ancienneté
        $years = $user->created_at->diffInYears(now());
        if ($years >= 1) {
            $indicators[] = [
                'icon' => 'heroicon-o-calendar',
                'label' => "Membre depuis {$years} an" . ($years > 1 ? 's' : ''),
                'color' => 'blue',
                'verified' => true,
            ];
        }

        // Note moyenne
        $avgRating = $user->receivedReviews()->avg('rating');
        if ($avgRating) {
            $indicators[] = [
                'icon' => 'heroicon-o-star',
                'label' => round($avgRating, 1) . '/5 (' . $user->receivedReviews()->count() . ' avis)',
                'color' => $avgRating >= 4 ? 'yellow' : 'gray',
                'verified' => true,
            ];
        }

        return $indicators;
    }

    /**
     * Vérifier si une transaction est suspecte
     */
    public function analyzeTransaction(Booking $booking): array
    {
        $flags = [];
        $riskScore = 0;

        // Montant élevé pour un nouveau compte
        $tenant = $booking->user;
        if ($tenant->created_at->diffInDays(now()) < 3 && $booking->total_price > 500000) {
            $riskScore += 25;
            $flags[] = 'Montant élevé pour un nouveau compte';
        }

        // Plusieurs réservations en peu de temps
        $recentBookings = $tenant->bookings()
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
        if ($recentBookings > 3) {
            $riskScore += 20;
            $flags[] = 'Nombreuses réservations en 24h';
        }

        // Réservation très longue durée
        $duration = $booking->check_in->diffInDays($booking->check_out);
        if ($duration > 180) {
            $riskScore += 15;
            $flags[] = 'Durée de réservation inhabituelle';
        }

        return [
            'risk_score' => $riskScore,
            'flags' => $flags,
            'requires_review' => $riskScore >= 30,
            'auto_approve' => $riskScore < 15,
        ];
    }
}
