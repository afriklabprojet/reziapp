<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Residence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'rezi:generate-sitemap';

    protected $description = 'Génère le sitemap.xml et invalide le cache de la route /sitemap.xml';

    public function handle(): int
    {
        $this->info('🗺️  Génération du sitemap...');

        $baseUrl = rtrim(config('app.url'), '/');
        $now = now()->toAtomString();

        $urls = [];

        // ── Pages statiques ──
        $staticPages = [
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => '/residences', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => '/residences/map', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => '/a-propos', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => '/faq', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => '/nous-contacter', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => '/conditions-utilisation', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => '/confidentialite', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => '/mentions-legales', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => '/guide-proprietaire', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ];

        foreach ($staticPages as $page) {
            $urls[] = [
                'loc' => $baseUrl.$page['loc'],
                'lastmod' => $now,
                'changefreq' => $page['changefreq'],
                'priority' => $page['priority'],
            ];
        }

        // ── Pages dynamiques (résidences publiées) ──
        $residences = Residence::approved()
            ->available()
            ->select(['id', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($residences as $residence) {
            $urls[] = [
                'loc' => $baseUrl.'/residences/'.$residence->id,
                'lastmod' => $residence->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // ── Pages par ville (SEO landing pages) ──
        $cities = City::active()->ordered()->get();
        foreach ($cities as $city) {
            $urls[] = [
                'loc' => $baseUrl.'/residences?city='.urlencode($city->name),
                'lastmod' => $now,
                'changefreq' => 'daily',
                'priority' => '0.7',
            ];
        }

        // ── Génération XML ──
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '  <url>'.PHP_EOL;
            $xml .= '    <loc>'.htmlspecialchars($url['loc']).'</loc>'.PHP_EOL;
            $xml .= '    <lastmod>'.$url['lastmod'].'</lastmod>'.PHP_EOL;
            $xml .= '    <changefreq>'.$url['changefreq'].'</changefreq>'.PHP_EOL;
            $xml .= '    <priority>'.$url['priority'].'</priority>'.PHP_EOL;
            $xml .= '  </url>'.PHP_EOL;
        }

        $xml .= '</urlset>'.PHP_EOL;

        // ── Écriture ──
        $path = public_path('sitemap.xml');
        File::put($path, $xml);

        $staticCount = count($staticPages);
        $residenceCount = $residences->count();

        $this->info(sprintf(
            '✅ Sitemap généré : %d pages statiques + %d résidences = %d URLs',
            $staticCount,
            $residenceCount,
            count($urls),
        ));
        $this->info('   → '.$path);
        $this->info('   Base URL : '.$baseUrl);

        // Invalider le cache de la route dynamique /sitemap.xml
        Cache::forget('sitemap_xml');
        $this->info('   Cache route /sitemap.xml invalidé');

        return self::SUCCESS;
    }
}
