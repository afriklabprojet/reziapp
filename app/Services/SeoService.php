<?php

namespace App\Services;

use App\Models\Residence;
use App\Models\SeoData;

class SeoService
{
    /**
     * Générer les données SEO pour une résidence
     */
    public function generateResidenceSeo(Residence $residence): SeoData
    {
        $title = $this->generateTitle($residence);
        $description = $this->generateDescription($residence);
        $keywords = $this->generateKeywords($residence);
        $structuredData = $this->generateStructuredData($residence);
        $ogData = $this->generateOpenGraphData($residence);

        return SeoData::updateOrCreate(
            [
                'seoable_type' => Residence::class,
                'seoable_id' => $residence->id,
                'locale' => 'fr',
            ],
            [
                'meta_title' => $title,
                'meta_description' => $description,
                'keywords' => $keywords,
                'structured_data' => $structuredData,
                'canonical_url' => route('residences.show', $residence),
                'og_data' => $ogData,
            ],
        );
    }

    /**
     * Générer le titre SEO optimisé
     */
    protected function generateTitle(Residence $residence): string
    {
        $type = $this->translateType($residence->type);
        $location = $residence->commune ?? $residence->city ?? 'Abidjan';
        $bedrooms = $residence->bedrooms ? "{$residence->bedrooms} ch." : '';

        $title = "{$type} meublé";
        if ($bedrooms) {
            $title .= " {$bedrooms}";
        }
        $title .= " à {$location}";

        if ($residence->price_per_night) {
            $price = number_format((float) $residence->price_per_night, 0, ',', ' ');
            $title .= " - {$price} FCFA/jour";
        }

        $title .= ' | Rezi App';

        // Limiter à 60 caractères
        if (strlen($title) > 60) {
            $title = "{$type} meublé à {$location} | Rezi App";
        }

        return $title;
    }

    /**
     * Générer la meta description
     */
    protected function generateDescription(Residence $residence): string
    {
        $type = $this->translateType($residence->type);
        $location = implode(', ', array_filter([
            $residence->commune,
            $residence->city ?? 'Abidjan',
            'Côte d\'Ivoire',
        ]));

        $features = [];
        if ($residence->bedrooms) {
            $features[] = "{$residence->bedrooms} chambre(s)";
        }
        if ($residence->bathrooms) {
            $features[] = "{$residence->bathrooms} salle(s) de bain";
        }
        if ($residence->surface_area) {
            $features[] = "{$residence->surface_area}m²";
        }

        $featuresText = $features ? ' - '.implode(', ', $features) : '';

        $price = $residence->price_per_night
            ? number_format((float) $residence->price_per_night, 0, ',', ' ').' FCFA/jour'
            : '';

        $description = "Louez ce {$type} meublé à {$location}{$featuresText}. {$price}. Réservation en ligne sécurisée sur Rezi App.";

        // Limiter à 155 caractères
        if (strlen($description) > 155) {
            $description = "Louez ce {$type} meublé à {$location}. {$price}. Réservation sécurisée sur Rezi App.";
        }

        return $description;
    }

    /**
     * Générer les mots-clés
     */
    protected function generateKeywords(Residence $residence): array
    {
        $keywords = [
            'location meublée',
            'appartement meublé',
            $residence->type,
            $residence->commune,
            $residence->city ?? 'Abidjan',
            'Côte d\'Ivoire',
            'location courte durée',
            'airbnb',
            'hébergement',
        ];

        // Ajouter les équipements
        if ($residence->relationLoaded('amenities')) {
            foreach ($residence->amenities->take(5) as $amenity) {
                $keywords[] = $amenity->name;
            }
        }

        // Type de location
        if ($residence->rental_type === 'colocation') {
            $keywords[] = 'colocation';
            $keywords[] = 'colocation étudiante';
        }
        if ($residence->rental_type === 'short_term') {
            $keywords[] = 'entrée coucher';
            $keywords[] = 'location journalière';
        }

        return array_filter(array_unique($keywords));
    }

    /**
     * Générer les données structurées JSON-LD
     */
    protected function generateStructuredData(Residence $residence): array
    {
        $photo = $residence->photos->first();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'LodgingBusiness',
            'name' => $residence->title,
            'description' => $residence->description,
            'url' => route('residences.show', $residence),
            'image' => $photo ? asset('storage/'.$photo->path) : null,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $residence->city ?? 'Abidjan',
                'addressRegion' => $residence->commune,
                'addressCountry' => 'CI',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $residence->latitude,
                'longitude' => $residence->longitude,
            ],
            'priceRange' => $residence->price_per_night
                ? number_format((float) $residence->price_per_night, 0).' FCFA'
                : null,
            'aggregateRating' => $residence->reviews_count > 0 ? [
                '@type' => 'AggregateRating',
                'ratingValue' => $residence->average_rating,
                'reviewCount' => $residence->reviews_count,
            ] : null,
            'amenityFeature' => $residence->amenities->map(fn ($a) => [
                '@type' => 'LocationFeatureSpecification',
                'name' => $a->name,
            ])->toArray(),
        ];
    }

    /**
     * Générer les données Open Graph
     */
    protected function generateOpenGraphData(Residence $residence): array
    {
        $photo = $residence->photos->first();

        return [
            'og:type' => 'website',
            'og:title' => $this->generateTitle($residence),
            'og:description' => $this->generateDescription($residence),
            'og:url' => route('residences.show', $residence),
            'og:image' => $photo ? asset('storage/'.$photo->path) : asset('images/og-default.jpg'),
            'og:site_name' => 'Rezi App',
            'og:locale' => 'fr_CI',
            'twitter:card' => 'summary_large_image',
            'twitter:site' => '@reziapp',
        ];
    }

    /**
     * Traduire le type de résidence
     */
    protected function translateType(string $type): string
    {
        return match($type) {
            'apartment' => 'Appartement',
            'studio' => 'Studio',
            'villa' => 'Villa',
            'room' => 'Chambre',
            'house' => 'Maison',
            'duplex' => 'Duplex',
            'penthouse' => 'Penthouse',
            default => ucfirst($type),
        };
    }

    /**
     * Générer le sitemap XML
     */
    public function generateSitemap(): string
    {
        $urls = [];

        // Pages statiques
        $staticPages = [
            ['url' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => route('residences.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => route('residences.map'), 'priority' => '0.8', 'changefreq' => 'daily'],
            ['url' => route('pages.about'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => route('pages.contact'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => route('pages.faq'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => route('pages.guide-proprietaire'), 'priority' => '0.6', 'changefreq' => 'monthly'],
        ];

        foreach ($staticPages as $page) {
            $urls[] = $this->formatSitemapUrl($page['url'], $page['priority'], $page['changefreq']);
        }

        // Résidences actives
        Residence::where('status', 'active')
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->chunk(500, function ($residences) use (&$urls) {
                foreach ($residences as $residence) {
                    $urls[] = $this->formatSitemapUrl(
                        route('residences.show', $residence),
                        '0.8',
                        'weekly',
                        $residence->updated_at->toW3cString(),
                    );
                }
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        $xml .= implode("\n", $urls);
        $xml .= "\n</urlset>";

        return $xml;
    }

    /**
     * Formater une URL pour le sitemap
     */
    protected function formatSitemapUrl(string $url, string $priority, string $changefreq, ?string $lastmod = null): string
    {
        $xml = "  <url>\n";
        $xml .= "    <loc>{$url}</loc>\n";
        if ($lastmod) {
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
        $xml .= "    <priority>{$priority}</priority>\n";
        $xml .= '  </url>';

        return $xml;
    }
}
