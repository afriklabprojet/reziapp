<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Residence;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Génère le sitemap.xml dynamiquement.
 *
 * Avantage par rapport au fichier statique : utilise toujours
 * la bonne APP_URL quel que soit l'environnement (dev, staging, prod).
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $cacheTtl = config('rezi.cache_ttl', 3600);

        $xml = Cache::remember('sitemap_xml', $cacheTtl, function () {
            return $this->generateSitemap();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }

    private function generateSitemap(): string
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $now = now()->toW3cString();

        $urls = [];

        // Pages statiques
        $staticPages = [
            ['loc' => '/', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => '/residences', 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => '/residences/map', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => '/a-propos', 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => '/faq', 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => '/nous-contacter', 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => '/conditions-utilisation', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => '/confidentialite', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => '/mentions-legales', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => '/guide-proprietaire', 'changefreq' => 'monthly', 'priority' => '0.6'],
        ];

        foreach ($staticPages as $page) {
            $urls[] = $this->buildUrlEntry(
                $baseUrl . $page['loc'],
                $now,
                $page['changefreq'],
                $page['priority'],
            );
        }

        // Résidences approuvées
        Residence::approved()
            ->select('id', 'updated_at')
            ->orderByDesc('updated_at')
            ->chunk(200, function ($residences) use (&$urls, $baseUrl) {
                foreach ($residences as $residence) {
                    $urls[] = $this->buildUrlEntry(
                        $baseUrl . '/residences/' . $residence->id,
                        $residence->updated_at->toW3cString(),
                        'weekly',
                        '0.8',
                    );
                }
            });

        // Pages par ville (SEO landing pages)
        $cities = City::active()->ordered()->get();
        foreach ($cities as $city) {
            $urls[] = $this->buildUrlEntry(
                $baseUrl . '/residences?city=' . urlencode($city->name),
                $now,
                'daily',
                '0.7',
            );
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= implode("\n", $urls);
        $xml .= "\n" . '</urlset>' . "\n";

        return $xml;
    }

    private function buildUrlEntry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        $escapedLoc = htmlspecialchars($loc, ENT_XML1, 'UTF-8');

        return <<<XML
  <url>
    <loc>{$escapedLoc}</loc>
    <lastmod>{$lastmod}</lastmod>
    <changefreq>{$changefreq}</changefreq>
    <priority>{$priority}</priority>
  </url>
XML;
    }
}
