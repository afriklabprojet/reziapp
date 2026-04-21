<?php

namespace App\Console\Commands;

use App\Services\SeoService;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'seo:sitemap {--ping : Ping les moteurs de recherche après génération}';
    protected $description = 'Génère le sitemap XML pour le SEO';

    public function handle(SeoService $seoService): int
    {
        $this->info('Génération du sitemap...');

        try {
            $seoService->generateSitemap();
            $this->info('✓ Sitemap généré avec succès: public/sitemap.xml');

            if ($this->option('ping')) {
                $this->pingSearchEngines();
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erreur lors de la génération: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function pingSearchEngines(): void
    {
        $sitemapUrl = urlencode(config('app.url').'/sitemap.xml');

        $engines = [
            'Google' => "https://www.google.com/ping?sitemap={$sitemapUrl}",
            'Bing' => "https://www.bing.com/ping?sitemap={$sitemapUrl}",
        ];

        foreach ($engines as $name => $pingUrl) {
            try {
                $response = \Illuminate\Support\Facades\Http::get($pingUrl);
                if ($response->successful()) {
                    $this->info("✓ {$name} notifié avec succès");
                } else {
                    $this->warn("⚠ {$name}: réponse {$response->status()}");
                }
            } catch (\Exception $e) {
                $this->warn("⚠ {$name}: ".$e->getMessage());
            }
        }
    }
}
