<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Thin environment-variable accessor with sandbox/production resolution.
 *
 * All configuration comes from environment variables (see .env.dist). Docker loads `.env` into the PHP container via
 * `env_file`, so these are plain getenv() reads.
 */
final class Env
{
    public static function get(string $name, string $default = ''): string
    {
        $value = getenv($name);

        return $value === false ? $default : (string) $value;
    }

    /** Sandbox unless SANDBOX_MODE is explicitly "false". */
    public static function sandboxMode(): bool
    {
        return self::get('SANDBOX_MODE', 'true') !== 'false';
    }

    public static function environment(): string
    {
        return self::sandboxMode() ? 'sandbox' : 'production';
    }

    public static function language(): string
    {
        return self::get('LANGUAGE', 'en');
    }

    /** Shared account API key for the active environment. */
    public static function apiKey(): string
    {
        return self::sandboxMode() ? self::get('SANDBOX_API_KEY') : self::get('API_KEY');
    }

    /** Shared account widget key for the active environment. */
    public static function widgetKey(): string
    {
        return self::sandboxMode() ? self::get('SANDBOX_WIDGET_KEY') : self::get('WIDGET_KEY');
    }

    /** Comfino REST API base URL — custom override wins, else the environment default. */
    public static function apiHost(): string
    {
        $custom = self::get('COMFINO_API_HOST_CUSTOM');

        if ($custom !== '') {
            return $custom;
        }

        return self::sandboxMode()
            ? self::get('COMFINO_API_HOST_SANDBOX', 'https://api-ecommerce.craty.pl')
            : self::get('COMFINO_API_HOST_PRODUCTION', 'https://api-ecommerce.comfino.pl');
    }
}
