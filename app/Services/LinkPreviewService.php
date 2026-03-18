<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class LinkPreviewService
{
    /**
     * Extraire l'aperçu Open Graph d'une URL
     */
    public function extract(string $url): ?array
    {
        // Vérifier que c'est une URL valide
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Cache pendant 24h
        $cacheKey = 'link_preview:' . md5($url);

        return Cache::remember($cacheKey, 86400, function () use ($url) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'REZI Bot/1.0'])
                    ->get($url);

                if (!$response->successful()) {
                    return null;
                }

                $html = $response->body();
                if (strlen($html) > 500000) {
                    // Trop gros, on évite le parsing
                    return null;
                }

                return $this->parseOpenGraph($html, $url);
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    /**
     * Parser les balises Open Graph du HTML
     */
    protected function parseOpenGraph(string $html, string $url): ?array
    {
        $data = [
            'url' => $url,
            'title' => null,
            'description' => null,
            'image' => null,
            'site_name' => null,
            'domain' => parse_url($url, PHP_URL_HOST),
        ];

        // OG Tags
        $ogPatterns = [
            'title' => '/<meta\s+(?:property|name)=["\']og:title["\']\s+content=["\']([^"\']*?)["\']/si',
            'description' => '/<meta\s+(?:property|name)=["\']og:description["\']\s+content=["\']([^"\']*?)["\']/si',
            'image' => '/<meta\s+(?:property|name)=["\']og:image["\']\s+content=["\']([^"\']*?)["\']/si',
            'site_name' => '/<meta\s+(?:property|name)=["\']og:site_name["\']\s+content=["\']([^"\']*?)["\']/si',
        ];

        // Also try content="..." property="..." order
        $ogPatternsReverse = [
            'title' => '/<meta\s+content=["\']([^"\']*?)["\']\s+(?:property|name)=["\']og:title["\']/si',
            'description' => '/<meta\s+content=["\']([^"\']*?)["\']\s+(?:property|name)=["\']og:description["\']/si',
            'image' => '/<meta\s+content=["\']([^"\']*?)["\']\s+(?:property|name)=["\']og:image["\']/si',
            'site_name' => '/<meta\s+content=["\']([^"\']*?)["\']\s+(?:property|name)=["\']og:site_name["\']/si',
        ];

        foreach ($ogPatterns as $key => $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $data[$key] = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            }
        }

        foreach ($ogPatternsReverse as $key => $pattern) {
            if (empty($data[$key]) && preg_match($pattern, $html, $matches)) {
                $data[$key] = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            }
        }

        // Fallback: <title>
        if (empty($data['title']) && preg_match('/<title[^>]*>([^<]+)<\/title>/si', $html, $matches)) {
            $data['title'] = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        }

        // Fallback: meta description
        if (empty($data['description'])) {
            if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']*?)["\']/si', $html, $matches)) {
                $data['description'] = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            }
        }

        // Si pas de titre, pas d'aperçu
        if (empty($data['title'])) {
            return null;
        }

        // Truncate
        $data['title'] = mb_substr($data['title'], 0, 120);
        $data['description'] = $data['description'] ? mb_substr($data['description'], 0, 200) : null;

        return $data;
    }

    /**
     * Extraire la première URL d'un texte
     */
    public function extractFirstUrl(string $text): ?string
    {
        if (preg_match('/https?:\/\/[^\s<>"\']+/i', $text, $matches)) {
            return $matches[0];
        }

        return null;
    }
}
