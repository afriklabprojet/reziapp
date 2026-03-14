<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'profile_photo',
        'provider',
        'provider_id',
        'avatar',
        'referral_code',
        'referred_by',
        'referral_balance',
        'wallet_credit',
        // KYC & Verification
        'email_verified',
        'phone_verified',
        'identity_verified',
        'identity_verified_at',
        'identity_verification_status',
        'identity_verification_data',
        'verification_level',
        'is_suspended',
        'suspended_until',
        'suspension_reason',
        'discrete_mode',
        'emergency_mode',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'trusted_device_token',
        'trusted_device_expires_at',
        'last_security_check',
        'last_login_ip',
        'last_login_at',
    ];

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
        ];
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is owner
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if user is regular user
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
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
     */
    public function residences()
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
            return $this->avatar;
        }

        if ($this->profile_photo) {
            return asset('storage/'.$this->profile_photo);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=10b981&color=fff';
    }

    /**
     * Nombre de messages non lus
     */
    public function unreadMessagesCount(): int
    {
        return Message::whereHas('conversation', function ($query) {
            $query->where('user_id', $this->id)
                ->orWhere('owner_id', $this->id);
        })
        ->where('sender_id', '!=', $this->id)
        ->whereNull('read_at')
        ->count();
    }

    /**
     * Historique de recherche
     */
    public function searchHistories()
    {
        return $this->hasMany(SearchHistory::class);
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
        return $this->referralsMade()
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

    /**
     * Calculer le prochain niveau de vérification.
     * Utilisé par IdentityVerification::approve() pour MAJ automatique.
     */
    public function getNextVerificationLevel(): string
    {
        $points = 0;

        if ($this->email_verified_at || $this->email_verified) {
            $points += 10;
        }
        if ($this->phone_verified) {
            $points += 20;
        }
        // +40 pour identité (sera true après approve)
        $points += 40;

        if ($this->profile_photo || $this->avatar) {
            $points += 5;
        }
        if ($this->created_at && $this->created_at->isBefore(now()->subMonths(6))) {
            $points += 10;
        }
        if ($this->reviews()->where('rating', '>=', 4)->count() >= 3) {
            $points += 15;
        }

        $points = min($points, 100);

        return match (true) {
            $points >= 80 => 'trusted',
            $points >= 60 => 'premium',
            $points >= 40 => 'standard',
            $points >= 20 => 'basic',
            default => 'none',
        };
    }

    /**
     * Vérifier si l'identité est vérifiée et valide
     */
    public function isIdentityVerified(): bool
    {
        return (bool) $this->identity_verified;
    }

    /**
     * Vérifier si le téléphone est vérifié
     */
    public function isPhoneVerified(): bool
    {
        return (bool) $this->phone_verified;
    }

    /**
     * Vérifier si l'email est vérifié
     */
    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null || (bool) $this->email_verified;
    }

    /**
     * Vérifier si le profil KYC est complet (identité + téléphone + email)
     */
    public function isFullyVerified(): bool
    {
        return $this->isIdentityVerified()
            && $this->isPhoneVerified()
            && $this->isEmailVerified();
    }

    /**
     * Vérifier si l'utilisateur est suspendu
     */
    public function isSuspended(): bool
    {
        if (! $this->is_suspended) {
            return false;
        }

        // Suspension temporaire expirée ?
        if ($this->suspended_until && $this->suspended_until->isPast()) {
            $this->update([
                'is_suspended' => false,
                'suspended_until' => null,
                'suspension_reason' => null,
            ]);

            return false;
        }

        return true;
    }
}
