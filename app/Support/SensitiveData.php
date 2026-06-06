<?php

declare(strict_types=1);

namespace App\Support;

final class SensitiveData
{
    public static function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return $phone;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '****';
        }

        $visibleSuffix = strlen($digits) > 4 ? substr($digits, -4) : '';
        $maskedDigits = str_repeat('*', max(strlen($digits) - strlen($visibleSuffix), 4)).$visibleSuffix;

        return str_starts_with($phone, '+') ? '+'.$maskedDigits : $maskedDigits;
    }

    public static function maskEmail(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return $email;
        }

        if (! str_contains($email, '@')) {
            return self::maskString($email, 1);
        }

        [$localPart, $domain] = explode('@', $email, 2);

        return self::maskString($localPart, 1).'@'.self::maskDomain($domain);
    }

    public static function maskIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return $ip;
        }

        $binary = @inet_pton($ip);
        $maskedIp = '[masked-ip]';

        if ($binary !== false && strlen($binary) === 4) {
            $parts = unpack('C4', $binary);
            $maskedIp = sprintf('%d.%d.%d.0', $parts[1], $parts[2], $parts[3]);
        } elseif ($binary !== false && strlen($binary) === 16) {
            $hex = unpack('H*', $binary)[1] ?? '';
            $groups = str_split($hex, 4);
            $maskedIp = implode(':', array_slice($groups, 0, 4)).':****:****:****:****';
        }

        return $maskedIp;
    }

    public static function hash(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return hash('sha256', mb_strtolower(trim($value)));
    }

    private static function maskDomain(string $domain): string
    {
        $segments = explode('.', $domain, 2);
        $label = $segments[0];
        $suffix = isset($segments[1]) ? '.'.$segments[1] : '';

        return self::maskString($label, 1).$suffix;
    }

    private static function maskString(string $value, int $visiblePrefix): string
    {
        $prefix = mb_substr($value, 0, $visiblePrefix);
        $maskedLength = max(mb_strlen($value) - $visiblePrefix, 3);

        return $prefix.str_repeat('*', $maskedLength);
    }
}
