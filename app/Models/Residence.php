<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasGeoSearch;
use App\Models\Concerns\HasPricing;
use App\Observers\ResidenceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([ResidenceObserver::class])]
class Residence extends Model
{
    use HasFactory;
    use HasGeoSearch;
    use HasPricing;
    use SoftDeletes;

    // Type de location
    public const TYPE_LOC_APARTMENT = 'apartment';
    public const TYPE_LOC_RESIDENCE = 'residence_meublee';
    public const TYPE_LOC_HOTEL = 'hotel';

    public const TYPES_LOCATION = [
        self::TYPE_LOC_APARTMENT => 'Appartement (location longue durée)',
        self::TYPE_LOC_RESIDENCE => 'Résidence meublée',
        self::TYPE_LOC_HOTEL => 'Hôtel',
    ];

    // Période de prix
    public const PRICE_PERIOD_DAY = 'day';
    public const PRICE_PERIOD_NIGHT = 'night';
    public const PRICE_PERIOD_MONTH = 'month';

    public const PRICE_PERIODS = [
        self::PRICE_PERIOD_DAY => 'Par jour',
        self::PRICE_PERIOD_NIGHT => 'Par nuit',
        self::PRICE_PERIOD_MONTH => 'Par mois',
    ];

    /**
     * Mapping type_location → price_period attendu.
     * Toutes les locations sont facturées à la journée.
     */
    public const TYPE_LOCATION_PRICE_MAP = [
        self::TYPE_LOC_APARTMENT => self::PRICE_PERIOD_DAY,
        self::TYPE_LOC_RESIDENCE => self::PRICE_PERIOD_DAY,
        self::TYPE_LOC_HOTEL     => self::PRICE_PERIOD_DAY,
    ];

    protected $hidden = ['location'];

    protected $fillable = [
        'owner_id',
        'category_id',
        'name',
        'description',
        'house_rules',
        'virtual_tour_url',
        'address',
        'country_code',
        'city',
        'commune',
        'quartier',
        'latitude',
        'longitude',
        'price_per_day',
        'price_per_week',
        'price_per_month',
        'bedrooms',
        'bathrooms',
        'max_guests',
        'min_nights',
        'max_nights',
        'check_in_time',
        'check_out_time',
        'type',
        'rental_type',
        'type_location',
        'price_period',
        'surface_area',
        'status',
        'approved_at',
        'is_available',
        'available_from',
        'instant_book',
        'cancellation_policy_id',
        'is_accessible',
        'accessibility_features',
        'is_verified',
        'is_top_residence',
        'verified_at',
        'average_rating',
        'reviews_count',
        // Règles de la maison
        'pets_allowed',
        'smoking_allowed',
        'parties_allowed',
        'floor',
        'has_elevator',
        // Location type
        'deposit_negotiable',
        'deposit_terms',
        'lease_type',
        'target_tenants',
        // Champs de modération
        'rejection_reason',
        'rejection_details',
        'changes_requested',
        'change_message',
        'moderated_by',
        'moderated_at',
        // Suspension
        'is_suspended',
        'suspension_reason',
        'suspended_at',
        'resume_at',
        'suspension_note',
        // Performance
        'views_count',
        'contacts_count',
        'performance_score',
        'listing_score',
        'listing_quality_score',
        // Nouvelles fonctionnalités Airbnb/Booking
        'cleaning_fee',
        'sustainability_score',
        'is_work_travel_ready',
        'bookings_this_month',
        'active_viewers_24h',
        'response_rate',
        'avg_response_time_hours',
    ];

    protected $casts = [
        'owner_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'price_per_day' => 'decimal:2',
        'price_per_week' => 'decimal:2',
        'price_per_month' => 'decimal:2',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'max_guests' => 'integer',
        'min_nights' => 'integer',
        'max_nights' => 'integer',
        'surface_area' => 'integer',
        'floor' => 'integer',
        'is_available' => 'boolean',
        'available_from' => 'date',
        'instant_book' => 'boolean',
        'is_accessible' => 'boolean',
        'accessibility_features' => 'array',
        'is_verified' => 'boolean',
        'is_top_residence' => 'boolean',
        'verified_at' => 'datetime',
        'house_rules' => 'string',  // Le formulaire envoie du texte, pas un JSON
        'average_rating' => 'decimal:1',
        'reviews_count' => 'integer',
        // Règles de la maison
        'pets_allowed' => 'boolean',
        'smoking_allowed' => 'boolean',
        'parties_allowed' => 'boolean',
        'has_elevator' => 'boolean',
        'deposit_negotiable' => 'boolean',
        'target_tenants' => 'array',
        'type_location' => 'string',
        'price_period' => 'string',
        // Moderation fields
        'moderated_at' => 'datetime',
        'moderated_by' => 'integer',
        'changes_requested' => 'array',
        // Suspension
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
        'resume_at' => 'datetime',
        // Nouvelles fonctionnalités
        'cleaning_fee' => 'decimal:2',
        'sustainability_score' => 'integer',
        'is_work_travel_ready' => 'boolean',
        'bookings_this_month' => 'integer',
        'active_viewers_24h' => 'integer',
        'response_rate' => 'decimal:1',
        'avg_response_time_hours' => 'integer',
    ];

    // ===== ACCESSORS (compatibilité avec les vues) =====

    /** Alias: $residence->title → $residence->name — 110+ views use ->title */
    public function getTitleAttribute(): string
    {
        return $this->name ?? '';
    }

    public function getTypeLocationLabelAttribute(): string
    {
        return self::TYPES_LOCATION[$this->type_location] ?? 'Résidence meublée';
    }

    public function getExpectedPricePeriodAttribute(): string
    {
        return self::TYPE_LOCATION_PRICE_MAP[$this->type_location] ?? self::PRICE_PERIOD_DAY;
    }

    // ===== RELATIONSHIPS =====

    /**
     * Catégorie de la résidence
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Owner of the residence
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Moderator who reviewed the residence
     */
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Photos of the residence
     */
    public function photos()
    {
        return $this->hasMany(Photo::class)->orderBy('order');
    }

    /**
     * Primary photo
     */
    public function primaryPhoto()
    {
        return $this->hasOne(Photo::class)->where('is_primary', true);
    }

    /**
     * Amenities of the residence
     */
    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'residence_amenity');
    }

    /**
     * Contacts for this residence
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Bookings for this residence
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Views for this residence
     */
    public function views()
    {
        return $this->hasMany(ResidenceView::class);
    }

    /**
     * Statistics for this residence
     */
    public function statistics()
    {
        return $this->hasMany(Statistic::class);
    }

    /**
     * Prix saisonniers absolus (montants fixes FCFA par nuit/semaine/mois).
     *
     * @see SeasonalPrice — tarifs absolus pour le calendrier propriétaire et DynamicPricingService.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SeasonalPrice>
     */
    public function seasonalPrices()
    {
        return $this->hasMany(SeasonalPrice::class)->orderBy('start_date');
    }

    /**
     * Tarification saisonnière par multiplicateur (templates Côte d'Ivoire).
     *
     * @see SeasonalPricing — multiplicateurs utilisés par AvailabilityCalendar et l'API disponibilité.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SeasonalPricing>
     */
    public function seasonalPricing()
    {
        return $this->hasMany(SeasonalPricing::class)->orderBy('start_date');
    }

    /**
     * Calendrier de disponibilité
     */
    public function availabilityCalendar()
    {
        return $this->hasMany(AvailabilityCalendar::class);
    }

    /**
     * Dates bloquées par le propriétaire
     */
    public function blockedDates()
    {
        return $this->hasMany(\App\Models\BlockedDate::class);
    }

    /**
     * Connexions canaux externes (Airbnb, Booking, etc.)
     */
    public function channelListings()
    {
        return $this->hasMany(\App\Models\ChannelListing::class);
    }

    /**
     * Vérifie la disponibilité pour une période
     */
    public function isAvailableFor(\Carbon\Carbon $checkIn, \Carbon\Carbon $checkOut): bool
    {
        return AvailabilityCalendar::isAvailable($this->id, $checkIn, $checkOut);
    }

    /**
     * Récupère les dates bloquées
     */
    public function getBlockedDates(?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): \Illuminate\Support\Collection
    {
        return AvailabilityCalendar::getBlockedDates($this->id, $from, $to);
    }

    /**
     * Photos 360° de la résidence
     */
    public function photos360()
    {
        return $this->hasMany(Photo360::class)->orderBy('order');
    }

    /**
     * Badges de la résidence
     */
    public function badges()
    {
        return $this->hasMany(ResidenceBadge::class);
    }

    /**
     * Badges actifs
     */
    public function activeBadges()
    {
        return $this->badges()->active();
    }

    /**
     * Points d'intérêt à proximité
     */
    public function pointsOfInterest()
    {
        return $this->hasMany(PointOfInterest::class)->orderBy('distance_meters');
    }

    /**
     * Avis clients
     */
    public function reviews()
    {
        return $this->hasMany(Review::class)->latest();
    }

    /**
     * Prix journaliers spécifiques
     */
    public function dailyPrices()
    {
        return $this->hasMany(DailyPrice::class)->orderBy('date');
    }

    /**
     * Co-hôtes de la résidence
     */
    public function coHosts()
    {
        return $this->hasMany(CoHost::class);
    }

    /**
     * Co-hôtes actifs
     */
    public function activeCoHosts()
    {
        return $this->coHosts()->accepted();
    }

    /**
     * Scope for approved residences
     * Accepts both 'active' (MySQL post-migration) and 'approved' (SQLite tests)
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['active', 'approved']);
    }

    /**
     * Scope for available residences
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope: Résidences publiées et disponibles (approved + available).
     *
     * Remplace le motif répété where('status','active')->where('is_available',true)
     * dans les contrôleurs.
     */
    public function scopeListable($query)
    {
        return $query->approved()->available();
    }

    /**
     * Scope: Réservation instantanée
     */
    public function scopeInstantBook($query)
    {
        return $query->where('instant_book', true);
    }

    /**
     * Scope: Accessible PMR
     */
    public function scopeAccessible($query)
    {
        return $query->where('is_accessible', true);
    }

    /**
     * Scope: Disponibilité immédiate (disponible aujourd'hui ou dans le passé)
     */
    public function scopeAvailableNow($query)
    {
        return $query->where('is_available', true)
            ->where(function ($q) {
                $q->whereNull('available_from')
                  ->orWhere('available_from', '<=', now()->toDateString());
            });
    }

    /**
     * Scope: Par politique d'annulation
     */
    public function scopeWithCancellationPolicy($query, $policyId)
    {
        return $query->where('cancellation_policy_id', $policyId);
    }

    /**
     * Scope: Note minimale
     */
    public function scopeMinRating($query, float $rating)
    {
        return $query->where('average_rating', '>=', $rating);
    }

    /**
     * Scope: Avec promotions actives
     */
    public function scopeWithActivePromotions($query)
    {
        return $query->whereHas('promotions', function ($q) {
            $q->where('is_active', true)
              ->where('start_date', '<=', now())
              ->where('end_date', '>=', now());
        });
    }

    /**
     * Scope: Par équipements (tous les équipements requis doivent être présents).
     *
     * Remplace les N sous-requêtes EXISTS imbriquées par un seul JOIN avec HAVING,
     * ce qui réduit la complexité de O(N×rows) à O(rows) quelle que soit la taille
     * du tableau $amenityIds.
     */
    public function scopeWithAmenities($query, array $amenityIds): void
    {
        if (empty($amenityIds)) {
            return;
        }

        $count = count($amenityIds);

        $query->whereHas('amenities', static function ($q) use ($amenityIds) {
            $q->whereIn('amenities.id', $amenityIds);
        }, '>=', $count);
    }

    /**
     * Scope: Par type de logement
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Par fourchette de prix
     */
    public function scopePriceBetween($query, ?float $min = null, ?float $max = null)
    {
        if ($min !== null) {
            $query->where('price_per_month', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price_per_month', '<=', $max);
        }

        return $query;
    }

    /**
     * Relation: Politique d'annulation
     */
    public function cancellationPolicy()
    {
        return $this->belongsTo(CancellationPolicy::class);
    }

    /**
     * Vérifier si a une promotion active
     */
    public function hasActivePromotion(): bool
    {
        return $this->activePromotions()->exists();
    }

    /**
     * Increment views count (atomic, no model reload)
     */
    public function incrementViews()
    {
        static::where('id', $this->id)->increment('views_count');
    }

    /**
     * Increment contacts count
     */
    public function incrementContacts()
    {
        $this->increment('contacts_count');
    }

    // ==========================================
    // MARKETING RELATIONS
    // ==========================================

    /**
     * Promotions de la résidence
     */
    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Promotions actives
     */
    public function activePromotions()
    {
        return $this->promotions()->active();
    }

    /**
     * Listings sponsorisés
     */
    public function sponsoredListings()
    {
        return $this->hasMany(SponsoredListing::class);
    }

    /**
     * Listing sponsorisé actif
     */
    public function activeSponsoredListing()
    {
        return $this->sponsoredListings()->active()->first();
    }

    /**
     * Vérifier si la résidence est sponsorisée
     */
    public function isSponsored(): bool
    {
        return $this->sponsoredListings()->active()->exists();
    }

}
