<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasLoyalty;
use App\Models\Concerns\HasRoles;
use App\Models\Concerns\HasVerification;
use App\Models\Concerns\HasWithdrawalPin;
use App\Notifications\ResetPasswordFr;
use App\Notifications\VerifyEmailFr;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use HasLoyalty;
    use HasRoles;
    use HasVerification;
    use HasWithdrawalPin;
    use Notifiable;

    /**
     * Determine if the user can access the Filament panel.
     * Super admin et admin ont accès au panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'super_admin'], true);
    }

    /**
     * The attributes that are mass assignable.
     *
     * SECURITE : seuls les champs modifiables par l'utilisateur sont ici.
     * Les champs sensibles (role, is_suspended, financial, 2FA, etc.)
     * doivent être modifiés explicitement via $user->role = 'owner'.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'profile_photo',
        'provider',
        'provider_id',
        'avatar',
        'referral_code',
        'referred_by',
        // KYC & Verification (set by admin/system only via explicit assignment)
        'email_verified',
        'phone_verified',
        'identity_verification_status',
        'identity_verification_data',
        'discrete_mode',
        'emergency_mode',
        'last_login_ip',
        'last_login_at',
        'jeko_contact_id',
    ];

    // Tous les champs non listés dans $fillable sont automatiquement protégés
    // contre le mass assignment par Eloquent (role, is_guest, wallet_credit,
    // is_suspended, two_factor_secret, withdrawal_pin…).

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'trusted_device_token',
        'withdrawal_pin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // KYC & Verification
            'email_verified' => 'boolean',
            'phone_verified' => 'boolean',
            'identity_verified' => 'boolean',
            'identity_verified_at' => 'datetime',
            'identity_verification_data' => 'array',
            'is_suspended' => 'boolean',
            'suspended_until' => 'datetime',
            'discrete_mode' => 'boolean',
            'emergency_mode' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'trusted_device_expires_at' => 'datetime',
            'last_security_check' => 'datetime',
            'last_login_at' => 'datetime',
            'withdrawal_pin_set_at' => 'datetime',
            'withdrawal_pin_locked_until' => 'datetime',
            // Fidélité
            'loyalty_points' => 'integer',
            'loyalty_bookings_count' => 'integer',
            'loyalty_nights_count' => 'integer',
            'loyalty_total_spent' => 'decimal:2',
            'loyalty_tier_upgraded_at' => 'datetime',
        ];
    }

    /**
     * Bookings made by this user (as a tenant/locataire)
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    /**
     * Bookings received by this user (as an owner/propriétaire)
     */
    public function ownerBookings()
    {
        return $this->hasManyThrough(Booking::class, Residence::class, 'owner_id', 'residence_id');
    }

    /**
     * Residences owned by this user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Residence>
     */
    public function residences(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Residence::class, 'owner_id');
    }

    /**
     * Contacts reçus (pour les propriétaires)
     */
    public function receivedContacts()
    {
        return $this->hasMany(Contact::class, 'owner_id');
    }

    /**
     * Contacts envoyés (pour les utilisateurs)
     */
    public function sentContacts()
    {
        return $this->hasMany(Contact::class, 'user_id');
    }

    /**
     * Pending contacts count for owners
     */
    public function pendingContactsCount(): int
    {
        return $this->receivedContacts()->where('status', 'pending')->count();
    }

    /**
     * Approved residences count for owners
     */
    public function approvedResidencesCount(): int
    {
        return $this->residences()->where('status', 'active')->count();
    }

    /**
     * Conversations de l'utilisateur
     */
    public function conversations()
    {
        return Conversation::where(function ($q) {
            $q->where('user_id', $this->id)
                ->orWhere('owner_id', $this->id);
        })->orderBy('last_message_at', 'desc');
    }

    /**
     * Messages envoyés
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Avis laissés
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Notifications
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class)->orderBy('created_at', 'desc');
    }

    /**
     * Notifications non lues
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Favoris
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Résidences favorites
     */
    public function favoriteResidences()
    {
        return $this->belongsToMany(Residence::class, 'favorites')
            ->withPivot('notes')
            ->withTimestamps();
    }

    /**
     * Vérifier si une résidence est en favoris
     */
    public function hasFavorited($residence): bool
    {
        $residenceId = $residence instanceof Residence ? $residence->id : $residence;

        return $this->favorites()->where('residence_id', $residenceId)->exists();
    }

    /**
     * Abonnements de l'utilisateur
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Abonnement actif
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('current_period_end', '>', now())
            ->with('plan')
            ->first();
    }

    /**
     * Vérifier si l'utilisateur a un abonnement actif
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    /**
     * Obtenir le plan d'abonnement actuel
     */
    public function currentPlan(): ?SubscriptionPlan
    {
        return $this->activeSubscription()?->plan;
    }

    /**
     * Obtenir l'avatar ou une image par défaut
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            return str_starts_with($this->avatar, 'http')
                ? $this->avatar
                : asset('storage/'.$this->avatar);
        }

        if ($this->profile_photo) {
            return str_starts_with($this->profile_photo, 'http')
                ? $this->profile_photo
                : asset('storage/'.$this->profile_photo);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=10b981&color=fff';
    }

    /**
     * Nombre de messages non lus (mis en cache 60s par utilisateur).
     */
    public function unreadMessagesCount(): int
    {
        return (int) Cache::remember("unread_msgs_{$this->id}", 60, function () {
            return Message::whereHas('conversation', function ($query) {
                $query->where('user_id', $this->id)
                    ->orWhere('owner_id', $this->id);
            })
            ->where('sender_id', '!=', $this->id)
            ->whereNull('read_at')
            ->count();
        });
    }

    /**
     * Invalide le cache du compteur de messages non lus.
     * À appeler après lecture ou envoi d'un message.
     */
    public function invalidateUnreadMessagesCache(): void
    {
        Cache::forget("unread_msgs_{$this->id}");
    }

    /**
     * Historique de recherche
     */
    public function searchHistories()
    {
        return $this->hasMany(SearchHistory::class);
    }

    /**
     * Recherches sauvegardées (alertes)
     */
    public function savedSearches()
    {
        return $this->hasMany(SavedSearch::class);
    }

    /**
     * Alertes de prix
     */
    public function priceAlerts()
    {
        return $this->hasMany(PriceAlert::class);
    }

    /**
     * Contrats de bail (en tant que locataire)
     */
    public function leaseContracts()
    {
        return $this->hasMany(LeaseContract::class, 'tenant_id');
    }

    /**
     * Visites de résidences
     */
    public function residenceViews()
    {
        return $this->hasMany(ResidenceView::class)->orderBy('created_at', 'desc');
    }

    /**
     * Résidences récemment visitées (uniques)
     */
    public function recentlyViewedResidences(int $limit = 10)
    {
        return Residence::whereIn('id', function ($query) use ($limit) {
            $query->select('residence_id')
                ->from('residence_views')
                ->where('user_id', $this->id)
                ->orderBy('created_at', 'desc')
                ->distinct()
                ->limit($limit);
        })->with(['photos', 'amenities']);
    }

    // ==========================================
    // MARKETING RELATIONS
    // ==========================================

    /**
     * Coupons créés par cet utilisateur
     */
    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'user_id');
    }

    /**
     * Utilisations de coupons par cet utilisateur
     */
    public function couponUses()
    {
        return $this->hasMany(CouponUse::class);
    }

    /**
     * Parrainages effectués (en tant que parrain)
     */
    public function referralsMade()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * Parrainage reçu (en tant que filleul)
     */
    public function referralReceived()
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    /**
     * Campagnes marketing créées
     */
    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'user_id');
    }

    /**
     * Générer ou récupérer le code de parrainage
     */
    public function getReferralCode(): string
    {
        if (!$this->referral_code) {
            $this->referral_code = strtoupper(substr(md5($this->id.$this->email.time()), 0, 8));
            $this->save();
        }

        return $this->referral_code;
    }

    /**
     * Compter les parrainages validés
     */
    public function completedReferralsCount(): int
    {
        return $this->referralsMade()->where('status', 'rewarded')->count();
    }

    /**
     * Total des gains de parrainage
     */
    public function totalReferralEarnings(): int
    {
        return (int) $this->referralsMade()
            ->where('status', 'rewarded')
            ->sum('referrer_reward');
    }

    /**
     * Parrainages en attente de validation
     */
    public function pendingReferralsCount(): int
    {
        return $this->referralsMade()->where('status', 'pending')->count();
    }

    // ==========================================
    // AVIS & CONFIANCE RELATIONS
    // ==========================================

    /**
     * Badges de l'utilisateur
     */
    public function badges()
    {
        return $this->hasMany(Badge::class);
    }

    /**
     * Badges actifs de l'utilisateur
     */
    public function activeBadges()
    {
        return $this->badges()->active();
    }

    /**
     * Profil public
     */
    public function publicProfile()
    {
        return $this->hasOne(PublicProfile::class);
    }

    /**
     * Avis reçus (pour les propriétaires via leurs résidences)
     */
    public function receivedReviews()
    {
        return Review::whereHas('residence', function ($query) {
            $query->where('owner_id', $this->id);
        });
    }

    /**
     * Signalements d'avis effectués
     */
    public function reviewReports()
    {
        return $this->hasMany(ReviewReport::class, 'reporter_id');
    }

    /**
     * Votes "utile" effectués
     */
    public function helpfulVotes()
    {
        return $this->hasMany(ReviewHelpfulVote::class);
    }

    /**
     * Vérifier si l'utilisateur a un badge spécifique
     */
    public function hasBadge(string $badgeType): bool
    {
        return $this->activeBadges()->where('badge_type', $badgeType)->exists();
    }

    /**
     * Vérifier si l'utilisateur est Superhost
     */
    public function isSuperhost(): bool
    {
        return $this->hasBadge(Badge::TYPE_SUPERHOST);
    }

    /**
     * Vérifier si l'utilisateur a le badge réponse rapide
     */
    public function isFastResponder(): bool
    {
        return $this->hasBadge(Badge::TYPE_FAST_RESPONDER);
    }

    /**
     * Obtenir ou créer le profil public
     */
    public function getOrCreatePublicProfile(): PublicProfile
    {
        return PublicProfile::getOrCreateForUser($this);
    }

    /**
     * Note moyenne reçue (propriétaires)
     */
    public function getAverageReceivedRating(): ?float
    {
        $avg = $this->receivedReviews()->approved()->avg('rating');

        return $avg ? round($avg, 1) : null;
    }

    /**
     * Nombre total d'avis reçus
     */
    public function getTotalReceivedReviewsCount(): int
    {
        return $this->receivedReviews()->approved()->count();
    }

    /**
     * Note moyenne donnée (voyageurs)
     */
    public function getAverageGivenRating(): ?float
    {
        $avg = $this->reviews()->approved()->avg('rating');

        return $avg ? round($avg, 1) : null;
    }

    /**
     * Nombre total d'avis donnés
     */
    public function getTotalGivenReviewsCount(): int
    {
        return $this->reviews()->approved()->count();
    }

    // ==========================================
    // KYC & VÉRIFICATION
    // ==========================================

    /**
     * Vérifications d'identité de l'utilisateur
     */
    public function identityVerifications()
    {
        return $this->hasMany(IdentityVerification::class);
    }

    /**
     * Dernière vérification d'identité
     */
    public function latestIdentityVerification()
    {
        return $this->hasOne(IdentityVerification::class)->latestOfMany();
    }

    /**
     * Vérifications téléphone
     */
    public function phoneVerifications()
    {
        return $this->hasMany(PhoneVerification::class);
    }

    /**
     * Contacts d'urgence
     */
    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailFr());
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordFr($token));
    }
}
