<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service de traduction automatique des messages FR ↔ EN.
 *
 * Backends supportés (selon config/services.php) :
 *  - 'libretranslate' : API gratuite open-source (https://libretranslate.com)
 *  - 'deepl'          : API DeepL (clé requise)
 *  - 'stub'           : aucun appel externe, retourne le texte original
 *
 * Choix par défaut : stub (aucune API externe). Active via .env :
 *   TRANSLATION_DRIVER=libretranslate
 *   LIBRETRANSLATE_URL=https://libretranslate.de/translate
 */
class MessageTranslationService
{
    private string $driver;

    public function __construct()
    {
        $this->driver = config('services.translation.driver', 'stub');
    }

    /**
     * Détecte la langue (heuristique simple FR vs EN).
     */
    public function detectLocale(string $text): string
    {
        $text = strtolower($text);
        $frHints = ['le ', 'la ', 'les ', 'je ', 'tu ', 'nous ', 'vous ', 'est ', 'avec ', 'merci', 'bonjour', 'salut', 'oui', 'non'];
        $enHints = ['the ', 'i ', 'you ', 'we ', 'they ', 'is ', 'with ', 'thank', 'hello', 'hi ', 'yes', 'no '];

        $frScore = 0;
        $enScore = 0;
        foreach ($frHints as $h) {
            if (str_contains($text, $h)) {
                $frScore++;
            }
        }
        foreach ($enHints as $h) {
            if (str_contains($text, $h)) {
                $enScore++;
            }
        }

        return $enScore > $frScore ? 'en' : 'fr';
    }

    /**
     * Traduit un texte de $from vers $to.
     * Retourne null si traduction non disponible (stub mode).
     */
    public function translate(string $text, string $from, string $to): ?string
    {
        if ($from === $to || trim($text) === '') {
            return null;
        }

        $cacheKey = 'tr:'.md5("{$from}:{$to}:{$text}");

        return Cache::remember($cacheKey, 86400, function () use ($text, $from, $to) {
            try {
                return match ($this->driver) {
                    'libretranslate' => $this->libreTranslate($text, $from, $to),
                    'deepl' => $this->deepl($text, $from, $to),
                    default => null, // stub
                };
            } catch (\Throwable $e) {
                Log::warning('Translation failed', ['driver' => $this->driver, 'error' => $e->getMessage()]);

                return null;
            }
        });
    }

    /**
     * Persiste la traduction sur un message.
     */
    public function translateMessage(Message $message, string $targetLocale): bool
    {
        if (!$message->content || $message->translated_locale === $targetLocale) {
            return true;
        }

        $sourceLocale = $message->original_locale ?? $this->detectLocale($message->content);
        if ($sourceLocale === $targetLocale) {
            return true;
        }

        $translated = $this->translate($message->content, $sourceLocale, $targetLocale);
        if ($translated === null) {
            return false;
        }

        $message->update([
            'original_locale' => $sourceLocale,
            'translated_content' => $translated,
            'translated_locale' => $targetLocale,
            'translated_at' => now(),
        ]);

        return true;
    }

    private function libreTranslate(string $text, string $from, string $to): ?string
    {
        $url = config('services.translation.libretranslate_url', 'https://libretranslate.de/translate');
        /** @var \Illuminate\Http\Client\Response $resp */
        $resp = Http::timeout(8)->asJson()->post($url, [
            'q' => $text,
            'source' => $from,
            'target' => $to,
            'format' => 'text',
            'api_key' => config('services.translation.libretranslate_key'),
        ]);
        if ($resp->failed()) {
            return null;
        }

        return $resp->json('translatedText');
    }

    private function deepl(string $text, string $from, string $to): ?string
    {
        $key = config('services.translation.deepl_key');
        if (!$key) {
            return null;
        }

        /** @var \Illuminate\Http\Client\Response $resp */
        $resp = Http::timeout(8)->withHeaders(['Authorization' => 'DeepL-Auth-Key '.$key])
            ->asForm()->post('https://api-free.deepl.com/v2/translate', [
                'text' => $text,
                'source_lang' => strtoupper($from),
                'target_lang' => strtoupper($to),
            ]);
        if ($resp->failed()) {
            return null;
        }

        return $resp->json('translations.0.text');
    }
}
