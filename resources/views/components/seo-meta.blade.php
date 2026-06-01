{{-- Composant SEO avec Schema.org Structured Data --}}

@props([
    'title' => config('app.name', 'REZI'),
    'description' => 'Trouvez votre résidence meublée idéale',
    'keywords' => 'résidence, meublé, location, appartement, Côte d\'Ivoire, Burkina Faso',
    'image' => null,
    'type' => 'website',
    'url' => null,
    'residence' => null,
    'noindex' => false,
])

@php
    $fullTitle = $title . ' - ' . config('app.name');
    $canonicalUrl = $url ?? request()->url();
    $ogImage = $image ?? asset('images/og/default.png');

    // Calcul du schema selon le type de page
    $schema = null;

    if ($residence) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LodgingBusiness',
            '@id' => route('residences.show', $residence),
            'name' => $residence->title ?? $residence->name,
            'description' => Str::limit(strip_tags($residence->description ?? ''), 200),
            'image' => $residence->photos->first()?->url ?? $ogImage,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $residence->commune,
                'addressRegion' => $residence->city ?? $residence->quartier,
                'addressCountry' => $residence->country_code ?? 'CI',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $residence->latitude,
                'longitude' => $residence->longitude,
            ],
            'priceRange' => number_format($residence->price ?? ($residence->price_per_day ?? 0)) . ' FCFA',
            'amenityFeature' => ($residence->amenities ?? collect())
                ->map(
                    fn($a) => [
                        '@type' => 'LocationFeatureSpecification',
                        'name' => $a->name,
                    ],
                )
                ->values()
                ->toArray(),
            'aggregateRating' =>
                $residence->reviews_count > 0
                    ? [
                        '@type' => 'AggregateRating',
                        'ratingValue' => number_format($residence->average_rating ?? 0, 1),
                        'reviewCount' => $residence->reviews_count,
                    ]
                    : null,
        ];

        // Supprimer les valeurs nulles
        $schema = array_filter($schema, fn($v) => $v !== null);
    } else {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => config('app.url'),
            'description' => $description,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('residences.search') . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
@endphp

{{-- Balises Meta de base --}}
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

@if ($noindex)
    <meta name="robots" content="noindex, nofollow">
@else
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
@endif

{{-- Open Graph / Facebook --}}
<meta property="og:type" content="{{ $type }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:locale" content="fr_CI">
<meta property="og:site_name" content="{{ config('app.name') }}">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ $canonicalUrl }}">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $ogImage }}">

{{-- Spécifique résidence --}}
@if ($residence)
    <meta property="og:type" content="place">
    <meta property="place:location:latitude" content="{{ $residence->latitude }}">
    <meta property="place:location:longitude" content="{{ $residence->longitude }}">
    <meta property="product:price:amount"
        content="{{ $residence->price ?? ($residence->price_per_day ?? 0) }}">
    <meta property="product:price:currency" content="XOF">
@endif

{{-- Geo Tags --}}
<meta name="geo.region" content="CI-AB">
<meta name="geo.placename" content="{{ $residence?->commune ?? ($residence?->city ?? 'REZI') }}">
@if ($residence && $residence->latitude && $residence->longitude)
    <meta name="geo.position" content="{{ $residence->latitude }};{{ $residence->longitude }}">
    <meta name="ICBM" content="{{ $residence->latitude }}, {{ $residence->longitude }}">
@endif

{{-- Structured Data / JSON-LD --}}
<script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

{{-- Breadcrumb Schema --}}
@if (isset($breadcrumbs) && count($breadcrumbs) > 0)
    <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($breadcrumbs)->map(fn($crumb, $index) => [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'name' => $crumb['name'],
        'item' => $crumb['url'],
    ])->values()->toArray(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif

{{-- Organization Schema (page d'accueil) --}}
@if (request()->routeIs('home'))
    <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => config('app.name'),
    'url' => config('app.url'),
    'logo' => asset('images/logo-rezi.png'),
    'description' => "Plateforme de location de résidences meublées en Afrique de l'Ouest",
    'address' => [
        '@type' => 'PostalAddress',
        'addressLocality' => config('rezi.company.city', 'Abidjan'),
        'addressCountry' => 'CI',
    ],
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => config('rezi.company.phone'),
        'contactType' => 'customer service',
        'availableLanguage' => ['French'],
    ],
    'sameAs' => array_values(array_filter([
        config('rezi.social.facebook'),
        config('rezi.social.instagram'),
        config('rezi.social.twitter'),
    ])),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif

{{-- FAQ Schema --}}
@if (isset($faqs) && count($faqs) > 0)
    <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect($faqs)->map(fn($faq) => [
        '@type' => 'Question',
        'name' => $faq['question'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $faq['answer'],
        ],
    ])->values()->toArray(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif

{{-- Preconnect pour performance --}}
<link rel="preconnect" href="https://api.mapbox.com">

{{-- DNS Prefetch --}}
<link rel="dns-prefetch" href="//api.mapbox.com">
