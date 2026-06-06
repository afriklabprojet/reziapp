<?php

declare(strict_types=1);

namespace App\Services;

class PublicUrlGuard
{
    /**
     * Vérifie qu'une URL pointe vers une cible HTTP publique.
     *
     * @param  list<string>  $allowedHostSuffixes
     */
    public function isSafe(string $url, array $allowedHostSuffixes = []): bool
    {
        $isSafe = filter_var($url, FILTER_VALIDATE_URL) !== false;
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = parse_url($url, PHP_URL_HOST);
        $hasValidScheme = in_array($scheme, ['http', 'https'], true);
        $hasCredentials = parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null;
        $hasHost = is_string($host) && $host !== '';
        $normalizedHost = $hasHost ? trim(strtolower($host), '[]') : '';
        $isBlockedHost = in_array($normalizedHost, ['localhost', 'localhost.localdomain'], true);
        $resolvedIps = $hasHost ? $this->resolveHostIps($normalizedHost) : [];
        $hasResolvedIps = $resolvedIps !== [];
        $matchesAllowList = $this->matchesAllowedHostSuffixes($normalizedHost, $allowedHostSuffixes);

        $isSafe = $isSafe
            && $hasValidScheme
            && ! $hasCredentials
            && $hasHost
            && ! $isBlockedHost
            && $hasResolvedIps
            && $matchesAllowList;

        if ($isSafe) {
            foreach ($resolvedIps as $ip) {
                if (! $this->isPublicIp($ip)) {
                    $isSafe = false;
                    break;
                }
            }
        }

        return $isSafe;
    }

    /**
     * Résout un hôte vers toutes ses IPs A/AAAA.
     *
     * @return list<string>
     */
    public function resolveHostIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];

        foreach ([DNS_A => 'ip', DNS_AAAA => 'ipv6'] as $recordType => $field) {
            $records = @dns_get_record($host, $recordType) ?: [];

            foreach ($records as $record) {
                $value = $record[$field] ?? null;

                if (is_string($value) && $value !== '') {
                    $ips[] = $value;
                }
            }
        }

        return array_values(array_unique($ips));
    }

    public function isPublicIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        return ! in_array(strtolower($ip), ['127.0.0.1', '::1'], true);
    }

    /**
     * @param  list<string>  $allowedHostSuffixes
     */
    protected function matchesAllowedHostSuffixes(string $host, array $allowedHostSuffixes): bool
    {
        if ($allowedHostSuffixes === []) {
            return true;
        }

        foreach ($allowedHostSuffixes as $allowedHostSuffix) {
            $normalizedSuffix = ltrim(strtolower($allowedHostSuffix), '.');

            if ($normalizedSuffix !== '' && ($host === $normalizedSuffix || str_ends_with($host, '.'.$normalizedSuffix))) {
                return true;
            }
        }

        return false;
    }
}
