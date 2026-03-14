<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Promotion;
use App\Models\Referral;
use App\Models\Residence;
use App\Models\SponsoredListing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MarketingService
{
    // ==========================================
    // PROMOTIONS
    // ==========================================

    /**
     * Créer une promotion flash
     */
    public function createPromotion(array $data): Promotion
    {
        return Promotion::create([
            'residence_id' => $data['residence_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'min_nights' => $data['min_nights'] ?? 1,
            'max_uses' => $data['max_uses'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Calculer le prix avec promotion
     */
    public function calculatePromotionalPrice(Residence $residence, ?Promotion $promotion = null): array
    {
        $originalPrice = $residence->price_per_day;

        if (!$promotion) {
            $promotion = $residence->activePromotions()->first();
        }

        if (!$promotion || !$promotion->isValid()) {
            return [
                'original' => $originalPrice,
                'final' => $originalPrice,
                'discount' => 0,
                'discount_percent' => 0,
                'promotion' => null,
            ];
        }

        $discount = $promotion->discount_type === 'percentage'
            ? ($originalPrice * $promotion->discount_value / 100)
            : $promotion->discount_value;

        $finalPrice = max(0, $originalPrice - $discount);
        $discountPercent = $originalPrice > 0
            ? round(($discount / $originalPrice) * 100)
            : 0;

        return [
            'original' => $originalPrice,
            'final' => $finalPrice,
            'discount' => $discount,
            'discount_percent' => $discountPercent,
            'promotion' => $promotion,
        ];
    }

    /**
     * Obtenir les résidences avec promotions actives
     */
    public function getResidencesWithActivePromotions(int $limit = 10)
    {
        return Residence::whereHas('promotions', function ($query) {
            $query->active();
        })
        ->with(['photos', 'promotions' => function ($query) {
            $query->active();
        }])
        ->approved()
        ->available()
        ->limit($limit)
        ->get();
    }

    // ==========================================
    // COUPONS
    // ==========================================

    /**
     * Générer un code coupon unique
     */
    public function generateCouponCode(string $prefix = ''): string
    {
        do {
            $code = $prefix.strtoupper(Str::random(8));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }

    /**
     * Créer un coupon
     */
    public function createCoupon(array $data): Coupon
    {
        return Coupon::create([
            'user_id' => $data['user_id'],
            'residence_id' => $data['residence_id'] ?? null,
            'code' => $data['code'] ?? $this->generateCouponCode(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'min_amount' => $data['min_amount'] ?? null,
            'max_discount' => $data['max_discount'] ?? null,
            'max_uses' => $data['max_uses'] ?? null,
            'max_uses_per_user' => $data['max_uses_per_user'] ?? 1,
            'starts_at' => $data['starts_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Valider un coupon
     */
    public function validateCoupon(string $code, User $user, ?Residence $residence = null, float $amount = 0): array
    {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return ['valid' => false, 'error' => 'Code coupon invalide'];
        }

        if (!$coupon->is_active) {
            return ['valid' => false, 'error' => 'Ce coupon n\'est plus actif'];
        }

        // Vérifier les dates
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return ['valid' => false, 'error' => 'Ce coupon n\'est pas encore valide'];
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return ['valid' => false, 'error' => 'Ce coupon a expiré'];
        }

        // Vérifier le nombre d'utilisations global
        if ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses) {
            return ['valid' => false, 'error' => 'Ce coupon a atteint son nombre maximum d\'utilisations'];
        }

        // Vérifier le nombre d'utilisations par utilisateur
        $userUses = CouponUse::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->count();

        if ($coupon->max_uses_per_user && $userUses >= $coupon->max_uses_per_user) {
            return ['valid' => false, 'error' => 'Vous avez déjà utilisé ce coupon'];
        }

        // Vérifier la résidence spécifique
        if ($coupon->residence_id && $residence && $coupon->residence_id !== $residence->id) {
            return ['valid' => false, 'error' => 'Ce coupon n\'est pas valide pour cette résidence'];
        }

        // Vérifier le montant minimum
        if ($coupon->min_amount && $amount < $coupon->min_amount) {
            return [
                'valid' => false,
                'error' => 'Le montant minimum requis est de '.number_format($coupon->min_amount, 0, ',', ' ').' FCFA',
            ];
        }

        // Calculer la réduction
        $discount = $this->calculateCouponDiscount($coupon, $amount);

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'final_amount' => max(0, $amount - $discount),
        ];
    }

    /**
     * Calculer la réduction d'un coupon
     */
    public function calculateCouponDiscount(Coupon $coupon, float $amount): float
    {
        if ($coupon->discount_type === 'percentage') {
            $discount = $amount * ($coupon->discount_value / 100);
        } else {
            $discount = $coupon->discount_value;
        }

        // Appliquer le plafond si défini
        if ($coupon->max_discount && $discount > $coupon->max_discount) {
            $discount = $coupon->max_discount;
        }

        return min($discount, $amount);
    }

    /**
     * Utiliser un coupon
     */
    public function useCoupon(Coupon $coupon, User $user, float $amount, float $discount): CouponUse
    {
        return DB::transaction(function () use ($coupon, $user, $amount, $discount) {
            // Enregistrer l'utilisation
            $couponUse = CouponUse::create([
                'coupon_id' => $coupon->id,
                'user_id' => $user->id,
                'original_amount' => $amount,
                'discount_amount' => $discount,
                'final_amount' => $amount - $discount,
            ]);

            // Incrémenter le compteur
            $coupon->increment('uses_count');

            return $couponUse;
        });
    }

    // ==========================================
    // PARRAINAGE
    // ==========================================

    /**
     * Générer un code de parrainage
     */
    public function generateReferralCode(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        do {
            $code = strtoupper(substr($user->name, 0, 3)).strtoupper(Str::random(5));
            $code = preg_replace('/[^A-Z0-9]/', '', $code);
        } while (User::where('referral_code', $code)->exists());

        $user->update(['referral_code' => $code]);

        return $code;
    }

    /**
     * Traiter un parrainage lors de l'inscription
     */
    public function processReferral(User $newUser, string $referralCode): ?Referral
    {
        $referrer = User::where('referral_code', $referralCode)->first();

        if (!$referrer || $referrer->id === $newUser->id) {
            return null;
        }

        // Vérifier si déjà parrainé
        if (Referral::where('referred_id', $newUser->id)->exists()) {
            return null;
        }

        $config = config('rezi.referral');

        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $newUser->id,
            'status' => 'pending',
            'referrer_reward' => $config['referrer_reward'] ?? 5000,
            'referred_reward' => $config['referred_reward'] ?? 2500,
            'reward_type' => 'credit',
        ]);

        // Notifier le parrain qu'un nouveau filleul s'est inscrit
        $referrer->notify(new \App\Notifications\ReferralCreated($referral));

        return $referral;
    }

    /**
     * Qualifier un parrainage (après première action)
     */
    public function qualifyReferral(User $user): ?Referral
    {
        $referral = Referral::where('referred_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$referral) {
            return null;
        }

        $referral->update([
            'status' => 'qualified',
            'qualified_at' => now(),
        ]);

        return $referral;
    }

    /**
     * Récompenser un parrainage
     */
    public function rewardReferral(Referral $referral): bool
    {
        if ($referral->status !== 'qualified') {
            return false;
        }

        return DB::transaction(function () use ($referral) {
            // Créditer le parrain
            $referrer = $referral->referrer;
            $referrer->increment('referral_balance', $referral->referrer_reward);

            // Créditer le filleul
            $referred = $referral->referred;
            $referred->increment('referral_balance', $referral->referred_reward);

            // Mettre à jour le statut
            $referral->update([
                'status' => 'rewarded',
                'rewarded_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Obtenir le classement des parrains
     */
    public function getReferralLeaderboard(int $limit = 10)
    {
        return User::select('users.*')
            ->selectRaw('COUNT(referrals.id) as referrals_count')
            ->selectRaw('SUM(CASE WHEN referrals.status = "rewarded" THEN referrals.referrer_reward ELSE 0 END) as total_earned')
            ->leftJoin('referrals', 'users.id', '=', 'referrals.referrer_id')
            ->groupBy('users.id')
            ->having('referrals_count', '>', 0)
            ->orderByDesc('referrals_count')
            ->limit($limit)
            ->get();
    }

    // ==========================================
    // CAMPAGNES
    // ==========================================

    /**
     * Créer une campagne
     */
    public function createCampaign(array $data): Campaign
    {
        return Campaign::create([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'email',
            'subject' => $data['subject'] ?? null,
            'content' => $data['content'],
            'template' => $data['template'] ?? null,
            'audience' => $data['audience'] ?? 'all_users',
            'audience_filters' => $data['audience_filters'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ]);
    }

    /**
     * Obtenir les destinataires d'une campagne
     */
    public function getCampaignRecipients(Campaign $campaign): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::query();

        switch ($campaign->audience) {
            case 'owners':
                $query->where('role', 'owner');
                break;
            case 'clients':
                $query->where('role', 'user');
                break;
            case 'inactive_users':
                $query->where('last_login_at', '<', now()->subDays(30));
                break;
            case 'new_users':
                $query->where('created_at', '>=', now()->subDays(7));
                break;
            case 'high_value':
                // Utilisateurs avec beaucoup de réservations/contacts
                $query->has('sentContacts', '>=', 5);
                break;
            case 'custom':
                // Appliquer les filtres personnalisés
                if ($campaign->audience_filters) {
                    $this->applyAudienceFilters($query, $campaign->audience_filters);
                }
                break;
        }

        // Exclure certains utilisateurs
        if ($campaign->excluded_user_ids) {
            $query->whereNotIn('id', $campaign->excluded_user_ids);
        }

        return $query->get();
    }

    /**
     * Appliquer les filtres d'audience personnalisés
     */
    protected function applyAudienceFilters($query, array $filters): void
    {
        if (isset($filters['commune'])) {
            $query->whereHas('residences', function ($q) use ($filters) {
                $q->where('commune', $filters['commune']);
            });
        }

        if (isset($filters['min_residences'])) {
            $query->has('residences', '>=', $filters['min_residences']);
        }

        if (isset($filters['registered_after'])) {
            $query->where('created_at', '>=', $filters['registered_after']);
        }

        if (isset($filters['registered_before'])) {
            $query->where('created_at', '<=', $filters['registered_before']);
        }
    }

    /**
     * Envoyer une campagne
     */
    public function sendCampaign(Campaign $campaign): array
    {
        if ($campaign->status === 'sent') {
            return ['success' => false, 'error' => 'Cette campagne a déjà été envoyée'];
        }

        $recipients = $this->getCampaignRecipients($campaign);
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $user) {
            try {
                $this->sendCampaignToUser($campaign, $user);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                \Log::error("Failed to send campaign {$campaign->id} to user {$user->id}: ".$e->getMessage());
            }
        }

        $campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
            'recipients_count' => $sent,
        ]);

        return [
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
            'total' => $recipients->count(),
        ];
    }

    /**
     * Envoyer une campagne à un utilisateur
     */
    protected function sendCampaignToUser(Campaign $campaign, User $user): CampaignSend
    {
        $content = $this->personalizeCampaignContent($campaign->content, $user);

        // Créer l'enregistrement d'envoi
        $send = CampaignSend::create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        try {
            switch ($campaign->type) {
                case 'email':
                    $this->sendCampaignEmail($campaign, $user, $content);
                    break;
                case 'sms':
                    $this->sendCampaignSms($user, $content);
                    break;
                case 'push':
                case 'in_app':
                    $this->sendCampaignNotification($user, $campaign->subject ?? $campaign->name, $content);
                    break;
            }

            $send->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            $send->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $send;
    }

    /**
     * Personnaliser le contenu de la campagne
     */
    protected function personalizeCampaignContent(string $content, User $user): string
    {
        $replacements = [
            '{{name}}' => $user->name,
            '{{first_name}}' => explode(' ', $user->name)[0],
            '{{email}}' => $user->email,
            '{{phone}}' => $user->phone ?? '',
            '{{referral_code}}' => $user->referral_code ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Envoyer un email de campagne
     */
    protected function sendCampaignEmail(Campaign $campaign, User $user, string $content): void
    {
        Mail::send([], [], function ($message) use ($campaign, $user, $content) {
            $message->to($user->email, $user->name)
                ->subject($campaign->subject ?? $campaign->name)
                ->html($content);
        });
    }

    /**
     * Envoyer un SMS de campagne
     */
    protected function sendCampaignSms(User $user, string $content): void
    {
        if (!$user->phone) {
            return;
        }

        try {
            app(SmsService::class)->send($user->phone, $content);
        } catch (\Throwable $e) {
            \Log::warning('Failed to send campaign SMS', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoyer une notification de campagne
     */
    protected function sendCampaignNotification(User $user, string $title, string $content): void
    {
        \App\Models\Notification::send($user, 'campaign', $title, $content);
    }

    // ==========================================
    // SPONSORED LISTINGS
    // ==========================================

    /**
     * Créer un listing sponsorisé
     */
    public function createSponsoredListing(array $data): SponsoredListing
    {
        // Pricing from config (keys: featured_home_price_weekly, top_search_price_weekly, etc.)
        $type = $data['type'] ?? 'premium_listing';
        $priceKey = "rezi.sponsored.{$type}_price_weekly";
        $weeklyPrice = config($priceKey, 7500);

        return SponsoredListing::create([
            'residence_id' => $data['residence_id'],
            'user_id' => $data['user_id'],
            'type' => $type,
            'daily_budget' => $data['daily_budget'] ?? null,
            'total_budget' => $data['total_budget'] ?? $weeklyPrice,
            'amount_spent' => 0,
            'billing_type' => $data['billing_type'] ?? 'flat_rate',
            'cost_per_unit' => $data['cost_per_unit'] ?? 0,
            'starts_at' => $data['starts_at'] ?? now(),
            'ends_at' => $data['ends_at'],
            'status' => 'pending',
            'is_paid' => false,
        ]);
    }

    /**
     * Activer un listing sponsorisé après paiement
     */
    public function activateSponsoredListing(SponsoredListing $sponsored): bool
    {
        if ($sponsored->status !== 'pending') {
            return false;
        }

        $sponsored->update([
            'status' => 'active',
            'starts_at' => now(),
        ]);

        return true;
    }

    /**
     * Enregistrer une impression
     */
    public function recordSponsoredImpression(SponsoredListing $sponsored): void
    {
        if ($sponsored->canRun()) {
            $sponsored->recordImpression();
        }
    }

    /**
     * Enregistrer un clic
     */
    public function recordSponsoredClick(SponsoredListing $sponsored): void
    {
        if ($sponsored->canRun()) {
            $sponsored->recordClick();
        }
    }

    /**
     * Obtenir les résidences sponsorisées pour l'affichage
     */
    public function getSponsoredResidences(string $type = 'premium_listing', int $limit = 5)
    {
        return Residence::whereHas('sponsoredListings', function ($query) use ($type) {
            $query->active()->where('type', $type);
        })
        ->with(['photos', 'sponsoredListings' => function ($query) use ($type) {
            $query->active()->where('type', $type);
        }])
        ->approved()
        ->available()
        ->inRandomOrder()
        ->limit($limit)
        ->get();
    }

    /**
     * Calculer le CTR d'un listing sponsorisé
     */
    public function calculateCTR(SponsoredListing $sponsored): float
    {
        if ($sponsored->impressions === 0) {
            return 0;
        }

        return round(($sponsored->clicks / $sponsored->impressions) * 100, 2);
    }

    // ==========================================
    // ANALYTICS
    // ==========================================

    /**
     * Statistiques marketing globales
     */
    public function getMarketingStats(): array
    {
        return [
            'promotions' => [
                'active' => Promotion::active()->count(),
                'total' => Promotion::count(),
                'uses' => Promotion::sum('uses_count'),
            ],
            'coupons' => [
                'active' => Coupon::active()->count(),
                'total' => Coupon::count(),
                'uses' => CouponUse::count(),
                'total_discount' => CouponUse::sum('discount_amount'),
            ],
            'referrals' => [
                'total' => Referral::count(),
                'pending' => Referral::where('status', 'pending')->count(),
                'rewarded' => Referral::where('status', 'rewarded')->count(),
                'total_rewards' => Referral::where('status', 'rewarded')
                    ->sum(DB::raw('referrer_reward + referred_reward')),
            ],
            'campaigns' => [
                'total' => Campaign::count(),
                'sent' => Campaign::where('status', 'sent')->count(),
                'emails_sent' => CampaignSend::where('status', 'sent')->count(),
            ],
            'sponsored' => [
                'active' => SponsoredListing::active()->count(),
                'total' => SponsoredListing::count(),
                'impressions' => SponsoredListing::sum('impressions'),
                'clicks' => SponsoredListing::sum('clicks'),
                'revenue' => SponsoredListing::sum('amount_spent'),
            ],
        ];
    }
}
