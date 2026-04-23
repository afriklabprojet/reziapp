<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\OwnerBadge;
use App\Models\Review;
use App\Models\User;
use Illuminate\Console\Command;

class CalculateOwnerBadges extends Command
{
    protected $signature = 'rezi:calculate-owner-badges {--user= : ID utilisateur spécifique}';

    protected $description = 'Calcule et attribue automatiquement les badges de confiance aux propriétaires';

    // Critères pour chaque badge
    private array $criteria = [
        'verified_identity' => [
            'description' => 'Identité vérifiée via document officiel',
        ],
        'verified_phone' => [
            'description' => 'Numéro de téléphone vérifié par SMS',
        ],
        'verified_residence' => [
            'description' => 'Au moins un logement vérifié sur place',
        ],
        'superhost' => [
            'min_rating' => 4.8,
            'min_reviews' => 10,
            'min_bookings' => 5,
            'max_cancellation_rate' => 0.01, // 1%
            'description' => 'Hôte exceptionnel avec excellentes performances',
        ],
        'trusted' => [
            'min_account_age_months' => 6,
            'min_active_listings' => 1,
            'min_completed_bookings' => 3,
            'description' => 'Propriétaire de confiance avec historique positif',
        ],
        'responsive' => [
            'max_response_time_minutes' => 60,
            'min_response_rate' => 0.90, // 90%
            'min_messages' => 10,
            'description' => 'Répond en moins d\'une heure à 90%+ des messages',
        ],
        'top_rated' => [
            'min_rating' => 4.9,
            'min_reviews' => 20,
            'description' => 'Dans le top des propriétaires les mieux notés',
        ],
    ];

    public function handle(): int
    {
        $userId = $this->option('user');

        if ($userId) {
            $owners = User::where('id', $userId)->where('role', 'owner')->get();
        } else {
            $owners = User::where('role', 'owner')->get();
        }

        $this->info("Analyse de {$owners->count()} propriétaires...");

        $stats = [
            'analyzed' => 0,
            'badges_awarded' => 0,
            'badges_revoked' => 0,
        ];

        $bar = $this->output->createProgressBar($owners->count());

        foreach ($owners as $owner) {
            $result = $this->analyzeAndAwardBadges($owner);
            $stats['analyzed']++;
            $stats['badges_awarded'] += $result['awarded'];
            $stats['badges_revoked'] += $result['revoked'];
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Analyse terminée:');
        $this->line("   - {$stats['analyzed']} propriétaires analysés");
        $this->line("   - {$stats['badges_awarded']} badges attribués");
        $this->line("   - {$stats['badges_revoked']} badges révoqués");

        return Command::SUCCESS;
    }

    private function analyzeAndAwardBadges(User $owner): array
    {
        $awarded = 0;
        $revoked = 0;

        // Collecter les métriques du propriétaire
        $metrics = $this->collectOwnerMetrics($owner);

        // 1. Badge Identité vérifiée
        if ($this->shouldHaveBadge('verified_identity', $metrics)) {
            if ($this->awardBadge($owner, OwnerBadge::TYPE_VERIFIED_IDENTITY, $metrics)) {
                $awarded++;
            }
        } else {
            if ($this->revokeBadge($owner, OwnerBadge::TYPE_VERIFIED_IDENTITY)) {
                $revoked++;
            }
        }

        // 2. Badge Téléphone vérifié
        if ($this->shouldHaveBadge('verified_phone', $metrics)) {
            if ($this->awardBadge($owner, OwnerBadge::TYPE_VERIFIED_PHONE, $metrics)) {
                $awarded++;
            }
        } else {
            if ($this->revokeBadge($owner, OwnerBadge::TYPE_VERIFIED_PHONE)) {
                $revoked++;
            }
        }

        // 3. Badge Logement vérifié
        if ($this->shouldHaveBadge('verified_residence', $metrics)) {
            if ($this->awardBadge($owner, OwnerBadge::TYPE_VERIFIED_RESIDENCE, $metrics)) {
                $awarded++;
            }
        } else {
            if ($this->revokeBadge($owner, OwnerBadge::TYPE_VERIFIED_RESIDENCE)) {
                $revoked++;
            }
        }

        // 4. Badge Superhôte (expire après 1 an, réévalué)
        if ($this->shouldHaveBadge('superhost', $metrics)) {
            if ($this->awardBadge($owner, OwnerBadge::TYPE_SUPERHOST, $metrics, 365)) {
                $awarded++;
            }
        } else {
            if ($this->revokeBadge($owner, OwnerBadge::TYPE_SUPERHOST)) {
                $revoked++;
            }
        }

        // 5. Badge Hôte de confiance
        if ($this->shouldHaveBadge('trusted', $metrics)) {
            if ($this->awardBadge($owner, OwnerBadge::TYPE_TRUSTED, $metrics)) {
                $awarded++;
            }
        } else {
            if ($this->revokeBadge($owner, OwnerBadge::TYPE_TRUSTED)) {
                $revoked++;
            }
        }

        // 6. Badge Réponse rapide (expire après 90 jours, réévalué)
        if ($this->shouldHaveBadge('responsive', $metrics)) {
            if ($this->awardBadge($owner, OwnerBadge::TYPE_RESPONSIVE, $metrics, 90)) {
                $awarded++;
            }
        } else {
            if ($this->revokeBadge($owner, OwnerBadge::TYPE_RESPONSIVE)) {
                $revoked++;
            }
        }

        // 7. Badge Top noté (expire après 180 jours, réévalué)
        if ($this->shouldHaveBadge('top_rated', $metrics)) {
            if ($this->awardBadge($owner, OwnerBadge::TYPE_TOP_RATED, $metrics, 180)) {
                $awarded++;
            }
        } else {
            if ($this->revokeBadge($owner, OwnerBadge::TYPE_TOP_RATED)) {
                $revoked++;
            }
        }

        return ['awarded' => $awarded, 'revoked' => $revoked];
    }

    private function collectOwnerMetrics(User $owner): array
    {
        // Statistiques de base
        $residences = $owner->residences()->where('status', 'active')->count();
        $verifiedResidences = $owner->residences()->whereNotNull('verified_at')->count();

        // Statistiques des réservations
        $completedBookings = Booking::whereHas('residence', fn ($q) => $q->where('owner_id', $owner->id))
            ->where('status', 'completed')
            ->count();

        $cancelledBookings = Booking::whereHas('residence', fn ($q) => $q->where('owner_id', $owner->id))
            ->where('status', 'cancelled')
            ->where('cancelled_by', 'owner')
            ->count();

        $totalBookings = Booking::whereHas('residence', fn ($q) => $q->where('owner_id', $owner->id))
            ->whereIn('status', ['completed', 'cancelled'])
            ->count();

        $cancellationRate = $totalBookings > 0 ? $cancelledBookings / $totalBookings : 0;

        // Statistiques des avis
        $reviews = Review::whereHas('residence', fn ($q) => $q->where('owner_id', $owner->id))->get();
        $avgRating = $reviews->avg('rating') ?? 0;
        $reviewsCount = $reviews->count();

        // Statistiques de réponse aux messages
        $responseStats = $this->calculateResponseStats($owner);

        // Âge du compte
        $accountAgeMonths = $owner->created_at->diffInMonths(now());

        return [
            'identity_verified' => !is_null($owner->identity_verified_at),
            'phone_verified' => $owner->phone_verified ?? false,
            'email_verified' => !is_null($owner->email_verified_at),
            'active_listings' => $residences,
            'verified_residences' => $verifiedResidences,
            'completed_bookings' => $completedBookings,
            'cancelled_bookings' => $cancelledBookings,
            'cancellation_rate' => $cancellationRate,
            'avg_rating' => round($avgRating, 2),
            'reviews_count' => $reviewsCount,
            'response_time_minutes' => $responseStats['avg_response_time'],
            'response_rate' => $responseStats['response_rate'],
            'total_messages_received' => $responseStats['total_received'],
            'account_age_months' => $accountAgeMonths,
        ];
    }

    private function calculateResponseStats(User $owner): array
    {
        // Trouver les conversations où le propriétaire est impliqué
        $conversations = Conversation::where('owner_id', $owner->id)->pluck('id');

        if ($conversations->isEmpty()) {
            return [
                'avg_response_time' => 0,
                'response_rate' => 1.0,
                'total_received' => 0,
            ];
        }

        // Messages reçus par le propriétaire (envoyés par les utilisateurs)
        $messagesReceived = Message::whereIn('conversation_id', $conversations)
            ->where('sender_id', '!=', $owner->id)
            ->where('created_at', '>=', now()->subMonths(3)) // 3 derniers mois
            ->orderBy('created_at')
            ->get();

        if ($messagesReceived->isEmpty()) {
            return [
                'avg_response_time' => 0,
                'response_rate' => 1.0,
                'total_received' => 0,
            ];
        }

        $responseTimes = [];
        $responded = 0;

        foreach ($messagesReceived as $received) {
            // Chercher la réponse du propriétaire
            $response = Message::where('conversation_id', $received->conversation_id)
                ->where('sender_id', $owner->id)
                ->where('created_at', '>', $received->created_at)
                ->orderBy('created_at')
                ->first();

            if ($response) {
                $responseTime = $received->created_at->diffInMinutes($response->created_at);
                // Ignorer si réponse après 24h (probablement pas une vraie réponse)
                if ($responseTime <= 1440) {
                    $responseTimes[] = $responseTime;
                    $responded++;
                }
            }
        }

        $avgResponseTime = count($responseTimes) > 0
            ? array_sum($responseTimes) / count($responseTimes)
            : 1440; // 24h par défaut si pas de données

        $responseRate = $messagesReceived->count() > 0
            ? $responded / $messagesReceived->count()
            : 0;

        return [
            'avg_response_time' => round($avgResponseTime),
            'response_rate' => round($responseRate, 2),
            'total_received' => $messagesReceived->count(),
        ];
    }

    private function shouldHaveBadge(string $badgeKey, array $metrics): bool
    {
        return match ($badgeKey) {
            'verified_identity' => $metrics['identity_verified'],

            'verified_phone' => $metrics['phone_verified'],

            'verified_residence' => $metrics['verified_residences'] >= 1,

            'superhost' =>
                $metrics['avg_rating'] >= $this->criteria['superhost']['min_rating'] &&
                $metrics['reviews_count'] >= $this->criteria['superhost']['min_reviews'] &&
                $metrics['completed_bookings'] >= $this->criteria['superhost']['min_bookings'] &&
                $metrics['cancellation_rate'] <= $this->criteria['superhost']['max_cancellation_rate'],

            'trusted' =>
                $metrics['account_age_months'] >= $this->criteria['trusted']['min_account_age_months'] &&
                $metrics['active_listings'] >= $this->criteria['trusted']['min_active_listings'] &&
                $metrics['completed_bookings'] >= $this->criteria['trusted']['min_completed_bookings'],

            'responsive' =>
                $metrics['total_messages_received'] >= $this->criteria['responsive']['min_messages'] &&
                $metrics['response_time_minutes'] <= $this->criteria['responsive']['max_response_time_minutes'] &&
                $metrics['response_rate'] >= $this->criteria['responsive']['min_response_rate'],

            'top_rated' =>
                $metrics['avg_rating'] >= $this->criteria['top_rated']['min_rating'] &&
                $metrics['reviews_count'] >= $this->criteria['top_rated']['min_reviews'],

            default => false,
        };
    }

    private function awardBadge(User $owner, string $badgeType, array $metrics, ?int $expiresInDays = null): bool
    {
        $existing = OwnerBadge::where('user_id', $owner->id)
            ->where('badge_type', $badgeType)
            ->where('status', OwnerBadge::STATUS_ACTIVE)
            ->first();

        // Si le badge existe et n'est pas expiré, on ne fait rien
        if ($existing && (!$existing->expires_at || $existing->expires_at->isFuture())) {
            // Mettre à jour les métadonnées
            $existing->update(['metadata' => $metrics]);

            return false;
        }

        $badgeInfo = OwnerBadge::$badges[$badgeType] ?? null;
        if (!$badgeInfo) {
            return false;
        }

        OwnerBadge::updateOrCreate(
            [
                'user_id' => $owner->id,
                'badge_type' => $badgeType,
            ],
            [
                'badge_name' => $badgeInfo['name'],
                'badge_icon' => $badgeInfo['icon'],
                'badge_color' => $badgeInfo['color'],
                'status' => OwnerBadge::STATUS_ACTIVE,
                'reason' => 'Attribué automatiquement - Critères remplis',
                'earned_at' => now(),
                'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
                'is_visible' => true,
                'metadata' => $metrics,
            ],
        );

        return true;
    }

    private function revokeBadge(User $owner, string $badgeType): bool
    {
        $existing = OwnerBadge::where('user_id', $owner->id)
            ->where('badge_type', $badgeType)
            ->where('status', OwnerBadge::STATUS_ACTIVE)
            ->first();

        if (!$existing) {
            return false;
        }

        $existing->update([
            'status' => OwnerBadge::STATUS_REVOKED,
            'reason' => 'Révoqué automatiquement - Critères non remplis',
        ]);

        return true;
    }
}
