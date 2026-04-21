<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    // Taux de change fixes pour les devises africaines (mise à jour mensuelle recommandée)
    // Base: 1 EUR
    protected array $exchangeRates = [
        'XOF' => 655.957,    // Franc CFA UEMOA (fixe avec EUR)
        'XAF' => 655.957,    // Franc CFA CEMAC (fixe avec EUR)
        'GNF' => 9200,       // Franc Guinéen (approximatif)
        'GHS' => 14.5,       // Cedi Ghanéen (approximatif)
        'NGN' => 1650,       // Naira (approximatif)
        'EUR' => 1,
        'USD' => 1.08,
    ];

    /**
     * Convertir un montant d'une devise à une autre
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rates = $this->getExchangeRates();

        // Convertir en EUR d'abord
        $eurAmount = $amount / ($rates[$fromCurrency] ?? 1);

        // Puis convertir dans la devise cible
        return round($eurAmount * ($rates[$toCurrency] ?? 1), 2);
    }

    /**
     * Obtenir les taux de change (avec cache)
     */
    public function getExchangeRates(): array
    {
        return Cache::remember('exchange_rates', 86400, function () {
            // En production, on pourrait récupérer les taux d'une API
            // Pour l'instant, utiliser les taux fixes
            return $this->exchangeRates;
        });
    }

    /**
     * Formater un prix selon la devise du pays
     */
    public function formatPrice(float $amount, string $currencyCode, bool $compact = false): string
    {
        $symbols = [
            'XOF' => 'FCFA',
            'XAF' => 'FCFA',
            'GNF' => 'GNF',
            'GHS' => 'GH₵',
            'NGN' => '₦',
            'EUR' => '€',
            'USD' => '$',
        ];

        $symbol = $symbols[$currencyCode] ?? $currencyCode;

        if ($compact) {
            $amount = $this->compactNumber($amount);
        } else {
            $amount = number_format($amount, 0, ',', ' ');
        }

        // Position du symbole selon la devise
        if (in_array($currencyCode, ['XOF', 'XAF', 'GNF'])) {
            return "{$amount} {$symbol}";
        }

        return "{$symbol}{$amount}";
    }

    /**
     * Compacter un nombre (ex: 150000 -> 150K)
     */
    protected function compactNumber(float $number): string
    {
        $suffixes = ['', 'K', 'M', 'B'];
        $suffixIndex = 0;

        while ($number >= 1000 && $suffixIndex < count($suffixes) - 1) {
            $number /= 1000;
            $suffixIndex++;
        }

        return round($number, 1).$suffixes[$suffixIndex];
    }

    /**
     * Obtenir la devise d'un pays
     */
    public function getCountryCurrency(string $countryCode): array
    {
        $country = Cache::remember("country_{$countryCode}", 3600, function () use ($countryCode) {
            return Country::where('code', $countryCode)->first();
        });

        if (!$country) {
            return [
                'code' => 'XOF',
                'symbol' => 'FCFA',
                'name' => 'Franc CFA',
            ];
        }

        return [
            'code' => $country->currency_code ?? 'XOF',
            'symbol' => $country->currency_symbol ?? 'FCFA',
            'name' => $country->currency_name ?? 'Franc CFA',
        ];
    }

    /**
     * Obtenir les informations multi-pays pour le frontend
     */
    public function getCountriesData(): array
    {
        return Cache::remember('countries_data', 3600, function () {
            return Country::where('is_active', true)
                ->get()
                ->map(fn ($country) => [
                    'code' => $country->code,
                    'name' => $country->name,
                    'flag' => $country->flag_emoji,
                    'phone_code' => $country->phone_code,
                    'currency' => [
                        'code' => $country->currency_code,
                        'symbol' => $country->currency_symbol,
                        'name' => $country->currency_name,
                    ],
                    'locale' => $country->locale,
                    'timezone' => $country->timezone,
                ])
                ->toArray();
        });
    }

    /**
     * Valider un numéro de téléphone selon le pays
     */
    public function validatePhoneNumber(string $phone, string $countryCode): bool
    {
        // Nettoyer le numéro
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $patterns = [
            'CI' => '/^(225)?[0-9]{10}$/',           // Côte d'Ivoire: 10 chiffres
            'SN' => '/^(221)?[0-9]{9}$/',            // Sénégal: 9 chiffres
            'ML' => '/^(223)?[0-9]{8}$/',            // Mali: 8 chiffres
            'BF' => '/^(226)?[0-9]{8}$/',            // Burkina Faso: 8 chiffres
            'GH' => '/^(233)?[0-9]{9,10}$/',         // Ghana: 9-10 chiffres
            'NG' => '/^(234)?[0-9]{10,11}$/',        // Nigeria: 10-11 chiffres
            'CM' => '/^(237)?[0-9]{9}$/',            // Cameroun: 9 chiffres
        ];

        $pattern = $patterns[$countryCode] ?? '/^[0-9]{8,15}$/';

        return (bool) preg_match($pattern, $phone);
    }

    /**
     * Formater un numéro de téléphone pour l'affichage
     */
    public function formatPhoneNumber(string $phone, string $countryCode): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $country = Country::where('code', $countryCode)->first();
        $phoneCode = $country->phone_code ?? '+225';

        // Supprimer l'indicatif s'il est déjà présent
        $codeDigits = preg_replace('/[^0-9]/', '', $phoneCode);
        if (str_starts_with($phone, $codeDigits)) {
            $phone = substr($phone, strlen($codeDigits));
        }

        // Formater selon le pays
        $formats = [
            'CI' => fn ($p) => preg_replace('/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1 $2 $3 $4 $5', $p),
            'SN' => fn ($p) => preg_replace('/(\d{2})(\d{3})(\d{2})(\d{2})/', '$1 $2 $3 $4', $p),
            'GH' => fn ($p) => preg_replace('/(\d{3})(\d{3})(\d{4})/', '$1 $2 $3', $p),
            'NG' => fn ($p) => preg_replace('/(\d{3})(\d{3})(\d{4})/', '$1 $2 $3', $p),
        ];

        $formatter = $formats[$countryCode] ?? fn ($p) => $p;

        return "{$phoneCode} ".$formatter($phone);
    }
}
