<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rezi App Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration spécifique à l'application Rezi App pour la recherche
    | de résidences meublées à Abidjan.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Informations entreprise
    |--------------------------------------------------------------------------
    */

    'company' => [
        'name' => env('REZI_COMPANY_NAME', 'rezi app'),
        'email' => env('REZI_COMPANY_EMAIL', 'contact@reziapp.ci'),
        'phone' => env('REZI_COMPANY_PHONE', '+225 07 00 00 00 00'),
        'phone_raw' => env('REZI_COMPANY_PHONE_RAW', '+2250700000000'),
        'address' => env('REZI_COMPANY_ADDRESS', "Abidjan, Cocody\nCôte d'Ivoire"),
        'website' => env('REZI_COMPANY_WEBSITE', 'www.reziapp.ci'),
        'tax_id' => env('REZI_COMPANY_TAX_ID', 'CI-000000000'),
        'city' => env('REZI_COMPANY_CITY', 'Abidjan'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Réseaux sociaux
    |--------------------------------------------------------------------------
    */

    'social' => [
        'facebook' => env('REZI_SOCIAL_FACEBOOK', 'https://facebook.com/reziapp.ci'),
        'instagram' => env('REZI_SOCIAL_INSTAGRAM', 'https://instagram.com/reziapp.ci'),
        'tiktok' => env('REZI_SOCIAL_TIKTOK', 'https://tiktok.com/@reziapp.ci'),
        'twitter' => env('REZI_SOCIAL_TWITTER', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tarification & Commissions
    |--------------------------------------------------------------------------
    */

    'pricing' => [
        'state_tax' => env('REZI_STATE_TAX', 1000),                        // 1 000 FCFA taxe d'État fixe (locataire uniquement)
        'owner_commission_rate' => env('REZI_OWNER_COMMISSION_RATE', 0.10), // 10% propriétaire (fallback, PlatformSetting a priorité)
        'min_withdrawal' => env('REZI_MIN_WITHDRAWAL', 5000),            // 5 000 FCFA minimum
    ],

    'invoice_to_owner' => env('REZI_INVOICE_TO_OWNER', false),

    /*
    |--------------------------------------------------------------------------
    | Valeurs par défaut des résidences
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'check_in_time'  => env('REZI_DEFAULT_CHECK_IN', '14h00'),
        'check_out_time' => env('REZI_DEFAULT_CHECK_OUT', '12h00'),
        'min_nights'     => (int) env('REZI_DEFAULT_MIN_NIGHTS', 1),
        'max_nights'     => (int) env('REZI_DEFAULT_MAX_NIGHTS', 365),
        'max_guests'     => (int) env('REZI_DEFAULT_MAX_GUESTS', 4),
        'city'           => env('REZI_DEFAULT_CITY', 'Abidjan'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Géolocalisation par défaut (Abidjan, Côte d'Ivoire)
    |--------------------------------------------------------------------------
    */

    'default_latitude' => env('DEFAULT_LATITUDE', 5.3600),
    'default_longitude' => env('DEFAULT_LONGITUDE', -4.0083),
    'default_search_radius_km' => env('DEFAULT_SEARCH_RADIUS_KM', 5),

    /*
    |--------------------------------------------------------------------------
    | Limites géographiques (CI + BF combinées — fallback si DB vide)
    |--------------------------------------------------------------------------
    */

    'geo' => [
        'bounds' => [
            'min_lat' => 4.30,
            'max_lat' => 15.10,
            'min_lng' => -8.60,
            'max_lng' => 2.40,
        ],
        'countries' => ['ci', 'bf'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Limites de recherche
    |--------------------------------------------------------------------------
    */

    'min_search_radius_km' => 1,
    'max_search_radius_km' => 50,
    'max_results_per_page' => 50,

    /*
    |--------------------------------------------------------------------------
    | Durée de cache par défaut (en secondes)
    |--------------------------------------------------------------------------
    |
    | Utilisé pour les requêtes homepage, zones, stats, filtres communes, etc.
    | Remplace les "3600" codés en dur dans les contrôleurs.
    |
    */

    'cache_ttl' => env('REZI_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Fine-grained cache TTL settings for different content types.
    |
    */

    'cache' => [
        'residence_ttl' => env('REZI_CACHE_RESIDENCE_TTL', 3600),    // 1 hour
        'search_ttl' => env('REZI_CACHE_SEARCH_TTL', 300),           // 5 minutes
        'commune_ttl' => env('REZI_CACHE_COMMUNE_TTL', 86400),       // 24 hours
        'stats_ttl' => env('REZI_CACHE_STATS_TTL', 1800),            // 30 minutes
        'featured_ttl' => env('REZI_CACHE_FEATURED_TTL', 900),       // 15 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Limites de pagination par défaut
    |--------------------------------------------------------------------------
    |
    | Centralisées ici plutôt que codées en dur dans chaque contrôleur.
    |
    */

    'pagination' => [
        'default' => 20,
        'bookings' => 10,
        'contacts' => 10,
        'reviews' => 10,
        'residences' => 12,
        'client_lists' => 15,
        'home_featured' => 6,
        'home_testimonials' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Recherche géolocalisée (rayons autorisés en mètres)
    |--------------------------------------------------------------------------
    */

    'search' => [
        'allowed_radii' => [2000, 5000, 10000, 25000, 50000],
        'default_radius' => 2000,
        'budget_threshold' => 100000,  // Seuil "budget" en FCFA
    ],

    /*
    |--------------------------------------------------------------------------
    | Codes promo — limites de réduction
    |--------------------------------------------------------------------------
    */

    'promo' => [
        'welcome_max_discount' => env('REZI_PROMO_WELCOME_MAX', 25000),   // FCFA
        'seasonal_max_discount' => env('REZI_PROMO_SEASONAL_MAX', 50000), // FCFA
    ],

    /*
    |--------------------------------------------------------------------------
    | Communes d'Abidjan (liste pour formulaires)
    |--------------------------------------------------------------------------
    */

    'communes' => [
        'Abobo', 'Adjamé', 'Anyama', 'Attécoubé', 'Bingerville',
        'Cocody', 'Koumassi', 'Marcory', 'Plateau', 'Port-Bouët',
        'Songon', 'Treichville', 'Yopougon',
    ],

    /*
    |--------------------------------------------------------------------------
    | Photos
    |--------------------------------------------------------------------------
    */

    'max_photos_per_residence' => env('MAX_PHOTOS_PER_RESIDENCE', 10),
    'max_photo_size_mb' => env('MAX_PHOTO_SIZE_MB', 5),
    'photo_dimensions' => [
        'thumbnail' => [150, 150],
        'medium' => [600, 400],
        'large' => [1200, 800],
    ],

    /*
    |--------------------------------------------------------------------------
    | Photo processing (queue job)
    |--------------------------------------------------------------------------
    */

    'photo_processing' => [
        'max_width' => env('PHOTO_MAX_WIDTH', 1920),
        'max_height' => env('PHOTO_MAX_HEIGHT', 1080),
        'quality' => env('PHOTO_QUALITY', 85),
        'thumb_width' => env('PHOTO_THUMB_WIDTH', 400),
        'thumb_height' => env('PHOTO_THUMB_HEIGHT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Zones d'Abidjan
    |--------------------------------------------------------------------------
    */

    'zones' => [
        'cocody' => [
            'name' => 'Cocody',
            'latitude' => 5.3485,
            'longitude' => -3.9910,
        ],
        'plateau' => [
            'name' => 'Plateau',
            'latitude' => 5.3200,
            'longitude' => -4.0200,
        ],
        'marcory' => [
            'name' => 'Marcory',
            'latitude' => 5.3010,
            'longitude' => -3.9780,
        ],
        'yopougon' => [
            'name' => 'Yopougon',
            'latitude' => 5.3590,
            'longitude' => -4.0760,
        ],
        'treichville' => [
            'name' => 'Treichville',
            'latitude' => 5.2980,
            'longitude' => -4.0010,
        ],
        'adjame' => [
            'name' => 'Adjamé',
            'latitude' => 5.3550,
            'longitude' => -4.0280,
        ],
        'abobo' => [
            'name' => 'Abobo',
            'latitude' => 5.4180,
            'longitude' => -4.0200,
        ],
        'koumassi' => [
            'name' => 'Koumassi',
            'latitude' => 5.2970,
            'longitude' => -3.9520,
        ],
        'port-bouet' => [
            'name' => 'Port-Bouët',
            'latitude' => 5.2580,
            'longitude' => -3.9260,
        ],
        'bingerville' => [
            'name' => 'Bingerville',
            'latitude' => 5.3560,
            'longitude' => -3.8880,
        ],
        'attécoubé' => [
            'name' => 'Attécoubé',
            'latitude' => 5.3298,
            'longitude' => -4.0456,
        ],
        'songon' => [
            'name' => 'Songon',
            'latitude' => 5.3167,
            'longitude' => -4.2500,
        ],
        'anyama' => [
            'name' => 'Anyama',
            'latitude' => 5.4973,
            'longitude' => -4.0517,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Types de résidences
    |--------------------------------------------------------------------------
    */

    'residence_types' => [
        'studio' => 'Studio',
        'appartement' => 'Appartement',
        'villa' => 'Villa',
        'chambre' => 'Chambre',
        'duplex' => 'Duplex',
        'penthouse' => 'Penthouse',
    ],

    /*
    |--------------------------------------------------------------------------
    | Équipements disponibles
    |--------------------------------------------------------------------------
    */

    'amenities' => [
        'climatisation' => ['name' => 'Climatisation', 'icon' => 'snowflake'],
        'wifi' => ['name' => 'WiFi', 'icon' => 'wifi'],
        'parking' => ['name' => 'Parking', 'icon' => 'car'],
        'piscine' => ['name' => 'Piscine', 'icon' => 'pool'],
        'securite' => ['name' => 'Sécurité 24h', 'icon' => 'shield'],
        'gardien' => ['name' => 'Gardien', 'icon' => 'user-shield'],
        'cuisine_equipee' => ['name' => 'Cuisine équipée', 'icon' => 'utensils'],
        'balcon' => ['name' => 'Balcon/Terrasse', 'icon' => 'sun'],
        'meuble' => ['name' => 'Meublé', 'icon' => 'couch'],
        'eau_chaude' => ['name' => 'Eau chaude', 'icon' => 'hot-tub'],
        'groupe_electrogene' => ['name' => 'Groupe électrogène', 'icon' => 'bolt'],
        'ascenseur' => ['name' => 'Ascenseur', 'icon' => 'elevator'],
    ],

    /*
    |--------------------------------------------------------------------------
    | KYC & Vérification
    |--------------------------------------------------------------------------
    |
    | Configuration du module Know Your Customer (KYC).
    | Gère les tentatives, délais, types de documents et niveaux.
    |
    */

    'kyc' => [
        // Identité
        'identity' => [
            'max_attempts' => env('KYC_MAX_ATTEMPTS', 3),
            'retry_cooldown_hours' => env('KYC_RETRY_COOLDOWN', 24),
            'verification_validity_years' => env('KYC_VALIDITY_YEARS', 2),
            'max_file_size_kb' => env('KYC_MAX_FILE_SIZE', 10240),
            'min_file_size_kb' => 50, // Un document scanné fait au moins 50 Ko
            'min_image_width' => 640,
            'min_image_height' => 400,
            'allowed_document_types' => ['cni', 'passport'],
            'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],

            // Formats de numéros de document (regex)
            'cni_patterns' => [
                '/^C\d{9}$/i',           // Ancienne CNI : C + 9 chiffres
                '/^CI\d{9,10}$/i',       // Nouvelle biométrique : CI + 9 ou 10 chiffres
                '/^\d{9,13}$/',          // Séquence numérique (9-13 chiffres)
            ],
            'passport_patterns' => [
                '/^[A-Z]{2}\d{7}$/i',   // CEDEAO : 2 lettres + 7 chiffres
                '/^[A-Z]\d{8}$/i',      // Variante : 1 lettre + 8 chiffres
                '/^[A-Z]{1,3}\d{6,9}$/i', // Format élargi
            ],

            // Expiration : empêcher les dates trop lointaines
            'max_expiry_years_cni' => 12,
            'max_expiry_years_passport' => 15,
            'min_remaining_days' => 30, // Avertissement si expire sous 30 jours
        ],

        // Téléphone
        'phone' => [
            'otp_length' => 6,
            'otp_expiry_minutes' => env('KYC_OTP_EXPIRY', 10),
            'max_otp_attempts' => 5,
            'max_resend_count' => 5,
            'resend_cooldown_seconds' => 60,
            'country_code' => env('KYC_COUNTRY_CODE', '+225'),
            'sms_provider' => env('KYC_SMS_PROVIDER', 'log'), // log, twilio, vonage, orange_sms
        ],

        // Niveaux de confiance (score → niveau)
        'trust_levels' => [
            'none' => 0,
            'basic' => 20,
            'standard' => 40,
            'premium' => 60,
            'trusted' => 80,
        ],

        // Points par critère
        'trust_points' => [
            'email_verified' => 10,
            'phone_verified' => 20,
            'identity_verified' => 40,
            'profile_photo' => 5,
            'account_age_6m' => 10,
            'positive_reviews_3' => 15,
        ],

        // Restrictions sans KYC
        'restrictions' => [
            'max_residences_unverified' => env('KYC_MAX_RESIDENCES_UNVERIFIED', 1),
            'require_identity_for_booking' => env('KYC_REQUIRE_IDENTITY_BOOKING', false),
            'require_phone_for_messaging' => env('KYC_REQUIRE_PHONE_MESSAGING', false),
        ],

        // Urgences
        'emergency' => [
            'max_contacts' => 3,
            'alert_types' => ['panic', 'sos', 'check_in_missed', 'suspicious', 'medical'],
        ],

        // Vérification automatique (Google Cloud Vision)
        'auto_verification' => [
            'enabled' => env('KYC_AUTO_ENABLED', false),

            // Seuils de score (sur 100)
            'auto_approve_threshold' => env('KYC_AUTO_APPROVE_THRESHOLD', 75),
            'auto_reject_threshold' => env('KYC_AUTO_REJECT_THRESHOLD', 20),

            // Seuil de correspondance faciale (0.0 à 1.0)
            'face_match_threshold' => env('KYC_FACE_MATCH_THRESHOLD', 0.6),

            // Logs détaillés (utile pour le debug)
            'debug_logging' => env('KYC_AUTO_DEBUG', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Programme de parrainage
    |--------------------------------------------------------------------------
    */

    'referral' => [
        'referrer_reward' => env('REFERRAL_REFERRER_REWARD', 5000), // FCFA
        'referred_reward' => env('REFERRAL_REFERRED_REWARD', 2500), // FCFA
        'referrer_reward_type' => env('REFERRAL_REFERRER_REWARD_TYPE', 'credit'), // credit, coupon
        'referred_reward_type' => env('REFERRAL_REFERRED_REWARD_TYPE', 'discount'), // discount, credit
        'min_booking_for_completion' => 1, // Nombre de réservations pour valider le parrainage
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketing & Publicité
    |--------------------------------------------------------------------------
    */

    'sponsored' => [
        'featured_home_price_weekly' => env('SPONSORED_FEATURED_HOME_PRICE', 25000), // FCFA/semaine
        'top_search_price_weekly' => env('SPONSORED_TOP_SEARCH_PRICE', 15000),
        'highlighted_price_weekly' => env('SPONSORED_HIGHLIGHTED_PRICE', 7500),
        'premium_price_weekly' => env('SPONSORED_PREMIUM_PRICE', 35000),
        'cost_per_click' => env('SPONSORED_COST_PER_CLICK', 50), // FCFA
        'cost_per_view' => env('SPONSORED_COST_PER_VIEW', 5), // FCFA
    ],

];
