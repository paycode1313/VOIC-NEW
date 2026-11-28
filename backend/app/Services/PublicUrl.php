<?php

namespace App\Services;

/**
 * The address other devices use to reach VOIC: the QR codes on reports and the booth screens.
 * VOIC_PUBLIC_URL (a LAN IP or tunnel) when set, otherwise the address of the current request.
 */
class PublicUrl
{
    public static function base(): string
    {
        return rtrim((string) (config('exam.public_url') ?: url('/')), '/');
    }

    public static function to(string $path): string
    {
        return self::base().'/'.ltrim($path, '/');
    }

    /**
     * False when the address only works on this laptop (localhost), so a phone scanning the QR gets nowhere.
     */
    public static function reachableFromPhone(): bool
    {
        $host = strtolower((string) parse_url(self::base(), PHP_URL_HOST));

        return ! in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]', '0.0.0.0'], true)
            && ! str_ends_with($host, '.localhost');
    }
}
