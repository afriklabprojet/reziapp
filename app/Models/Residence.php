<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\ResidenceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([ResidenceObserver::class])]
class Residence extends Model
{
    use HasFactory;
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

    /**
     * Alias: $residence->title → $residence->name
     * 110+ vues utilisent ->title au lieu de ->name
     */
    public function getTitleAttribute(): string
    {
        return $this->name ?? '';
    }

    /**
     * Alias: $residence->price_per_night → tarif journalier
     * Retourne le prix par jour, avec fallback depuis le tarif mensuel.
     */
    public function getPricePerNightAttribute(): ?string
    {
        if ($this->price_per_day && $this->price_per_day > 0) {
            return $this->price_per_day;
        }

        if ($this->price_per_month && $this->price_per_month > 0) {
            return (string) round($this->price_per_month / 30);
        }

        return null;
    }

    /**
     * $residence->price → prix d'affichage (toujours le tarif journalier)
     * Toutes les locations sont à la journée.
     */
    public function getPriceAttribute(): float
    {
        if (($this->price_per_day ?? 0) > 0) {
            return (float) $this->price_per_day;
        }

        if (($this->price_per_month ?? 0) > 0) {
            return (float) round($this->price_per_month / 30);
        }

        return 0;
    }

    /**
     * Label de période pour le prix d'affichage → toujours "jour"
     */
    public function getPriceLabelAttribute(): string
    {
        return 'jour';
    }

    /**
     * Prix d'affichage — toujours le tarif journalier.
     */
    public function getDisplayPriceAttribute(): float
    {
        return $this->price;
    }

    /**
     * Label du type de location (Appartement, Résidence meublée, Hôtel)
     */
    public function getTypeLocationLabelAttribute(): string
    {
        return self::TYPES_LOCATION[$this->type_location] ?? 'Résidence meublée';
    }

    /**
     * Détermine le price_period attendu en fonction du type_location.
     */
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
     * Prix saisonniers de la résidence
     */
    public function seasonalPrices()
    {
        return $this->hasMany(SeasonalPrice::class)->orderBy('start_date');
    }

    /**
     * Prix saisonniers avancés
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
     * Scope: Par équipements (tous les équipements requis)
     */
    public function scopeWithAmenities($query, array $amenityIds)
    {
        foreach ($amenityIds as $amenityId) {
            $query->whereHas('amenities', function ($q) use ($amenityId) {
                $q->where('amenities.id', $amenityId);
            });
        }

        return $query;
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

    /**
     * Calculate distance from given coordinates using Haversine formula
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return float Distance in meters
     */
    public function distanceFrom(float $lat, float $lng): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $latFrom = deg2rad($lat);
        $lngFrom = deg2rad($lng);
        $latTo = deg2rad($this->latitude);
        $lngTo = deg2rad($this->longitude);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    /**
     * Scope to find residences within radius.
     *
     * MySQL path (production): two-stage spatial query
     *   1. MBRContains(<bbox POLYGON>, location)  — uses SPATIAL INDEX, eliminates ~90% of rows
     *   2. ST_Distance_Sphere(location, <point>) <= $radius  — precise great-circle refinement
     *
     * SQLite path (tests): Haversine via bounding-box + whereRaw (SQLite has no spatial functions).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $lat Latitude of search centre
     * @param float $lng Longitude of search centre
     * @param int $radius Radius in metres
     * @param bool $sortByDistance Order results ascending by distance
     */
    public function scopeWithinRadius($query, float $lat, float $lng, int $radius, bool $sortByDistance = true)
    {
        // Bounding-box deltas in degrees
        $latDelta = $radius / 111320;
        $lngDelta = $radius / (111320 * cos(deg2rad($lat)));

        $minLat = $lat - $latDelta;
        $maxLat = $lat + $latDelta;
        $minLng = $lng - $lngDelta;
        $maxLng = $lng + $lngDelta;

        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return $this->scopeWithinRadiusSqlite(
                $query, $lat, $lng, $radius, $minLat, $maxLat, $minLng, $maxLng, $sortByDistance
            );
        }

        // MySQL: MBRContains drives the SPATIAL INDEX; ST_Distance_Sphere refines
        $bboxWkt  = "POLYGON(({$minLng} {$minLat},{$maxLng} {$minLat},{$maxLng} {$maxLat},{$minLng} {$maxLat},{$minLng} {$minLat}))";
        $bboxExpr = "ST_GeomFromText('{$bboxWkt}', 4326)";
        $ptExpr   = "ST_GeomFromText('POINT({$lng} {$lat})', 4326)";

        $query = $query
            ->whereRaw("MBRContains({$bboxExpr}, location)")
            ->whereRaw("ST_Distance_Sphere(location, {$ptExpr}) <= ?", [$radius])
            ->selectRaw("*, ST_Distance_Sphere(location, {$ptExpr}) AS distance_meters");

        if ($sortByDistance) {
            $query->orderBy('distance_meters', 'asc');
        }

        return $query;
    }

    /**
     * SQLite fallback for scopeWithinRadius (test environment).
     * Uses Haversine via bounding box + whereRaw — no spatial index available.
     */
    private function scopeWithinRadiusSqlite(
        $query,
        float $lat,
        float $lng,
        int $radius,
        float $minLat,
        float $maxLat,
        float $minLng,
        float $maxLng,
        bool $sortByDistance,
    ) {
        $earthRadius = 6371000;
        $radConst = 0.017453293;

        $distanceExpr = "(
            {$earthRadius} * acos(
                CASE
                    WHEN (
                        cos({$lat} * {$radConst}) * cos(latitude * {$radConst}) *
                        cos(longitude * {$radConst} - ({$lng}) * {$radConst}) +
                        sin({$lat} * {$radConst}) * sin(latitude * {$radConst})
                    ) > 1.0 THEN 1.0
                    WHEN (
                        cos({$lat} * {$radConst}) * cos(latitude * {$radConst}) *
                        cos(longitude * {$radConst} - ({$lng}) * {$radConst}) +
                        sin({$lat} * {$radConst}) * sin(latitude * {$radConst})
                    ) < -1.0 THEN -1.0
                    ELSE (
                        cos({$lat} * {$radConst}) * cos(latitude * {$radConst}) *
                        cos(longitude * {$radConst} - ({$lng}) * {$radConst}) +
                        sin({$lat} * {$radConst}) * sin(latitude * {$radConst})
                    )
                END
            )
        )";

        $query = $query
            ->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
            ->whereRaw("{$distanceExpr} <= ?", [$radius])
            ->selectRaw("*, {$distanceExpr} AS distance_meters");

        if ($sortByDistance) {
            $query->orderBy('distance_meters', 'asc');
        }

        return $query;
    }

    /**
     * Scope to find residences sorted by distance only (without radius limit).
     *
     * MySQL path: MBRContains on a 50 km bounding box drives the SPATIAL INDEX,
     * then ST_Distance_Sphere orders the candidate set.
     *
     * SQLite path: Haversine with no bounding-box pre-filter (full scan acceptable
     * in test environments with small datasets).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $lat Latitude of search centre
     * @param float $lng Longitude of search centre
     * @param int $limit Maximum number of results
     */
    public function scopeNearestTo($query, float $lat, float $lng, int $limit = 20)
    {
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return $this->scopeNearestToSqlite($query, $lat, $lng, $limit);
        }

        // Use a 50 km bounding box so the SPATIAL INDEX prunes distant rows
        $nearbyRadiusMeters = 50000;
        $latDelta = $nearbyRadiusMeters / 111320;
        $lngDelta = $nearbyRadiusMeters / (111320 * cos(deg2rad($lat)));

        $minLat = $lat - $latDelta;
        $maxLat = $lat + $latDelta;
        $minLng = $lng - $lngDelta;
        $maxLng = $lng + $lngDelta;

        $bboxWkt  = "POLYGON(({$minLng} {$minLat},{$maxLng} {$minLat},{$maxLng} {$maxLat},{$minLng} {$maxLat},{$minLng} {$minLat}))";
        $bboxExpr = "ST_GeomFromText('{$bboxWkt}', 4326)";
        $ptExpr   = "ST_GeomFromText('POINT({$lng} {$lat})', 4326)";

        return $query
            ->whereRaw("MBRContains({$bboxExpr}, location)")
            ->selectRaw("*, ST_Distance_Sphere(location, {$ptExpr}) AS distance_meters")
            ->orderBy('distance_meters', 'asc')
            ->limit($limit);
    }

    /**
     * SQLite fallback for scopeNearestTo (test environment).
     */
    private function scopeNearestToSqlite($query, float $lat, float $lng, int $limit)
    {
        $earthRadius = 6371000;
        $radConst = 0.017453293;

        $distanceExpr = "(
            {$earthRadius} * acos(
                CASE
                    WHEN (
                        cos({$lat} * {$radConst}) * cos(latitude * {$radConst}) *
                        cos(longitude * {$radConst} - ({$lng}) * {$radConst}) +
                        sin({$lat} * {$radConst}) * sin(latitude * {$radConst})
                    ) > 1.0 THEN 1.0
                    WHEN (
                        cos({$lat} * {$radConst}) * cos(latitude * {$radConst}) *
                        cos(longitude * {$radConst} - ({$lng}) * {$radConst}) +
                        sin({$lat} * {$radConst}) * sin(latitude * {$radConst})
                    ) < -1.0 THEN -1.0
                    ELSE (
                        cos({$lat} * {$radConst}) * cos(latitude * {$radConst}) *
                        cos(longitude * {$radConst} - ({$lng}) * {$radConst}) +
                        sin({$lat} * {$radConst}) * sin(latitude * {$radConst})
                    )
                END
            )
        )";

        return $query
            ->selectRaw("*, {$distanceExpr} AS distance_meters")
            ->orderBy('distance_meters', 'asc')
            ->limit($limit);
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

    /**
     * Obtenir le meilleur prix avec promotion
     */
    public function getBestPrice(): array
    {
        $originalPrice = $this->price_per_day;
        $activePromotion = $this->activePromotions()->first();

        if (!$activePromotion) {
            return [
                'original' => $originalPrice,
                'final' => $originalPrice,
                'discount' => 0,
                'discount_percent' => 0,
                'promotion' => null,
            ];
        }

        $discount = $activePromotion->discount_type === 'percentage'
            ? ($originalPrice * $activePromotion->discount_value / 100)
            : $activePromotion->discount_value;

        $finalPrice = max(0, $originalPrice - $discount);
        $discountPercent = $originalPrice > 0
            ? round(($discount / $originalPrice) * 100)
            : 0;

        return [
            'original' => $originalPrice,
            'final' => $finalPrice,
            'discount' => $discount,
            'discount_percent' => $discountPercent,
            'promotion' => $activePromotion,
        ];
    }
}
