<?php

namespace CacheDigital\Core;

/**
 * Configuração do SDK. Ordem para o endereço da API:
 *   1) valor informado no código;
 *   2) Laravel: config('cachedigital.url_digital_account');
 *   3) variável de ambiente CACHE_DIGITAL_URL;
 *   4) produção (https://api.cachebank.com.br).
 */
final class Config
{
    public const DEFAULT_BASE_URL = 'https://api.cachebank.com.br';
    public const DEFAULT_TIMEOUT = 10.0;
    public const VERSION = '1.1.0';

    public static function baseUrl(?string $explicit = null): string
    {
        $candidates = [$explicit];

        if (function_exists('config')) {
            try {
                $candidates[] = config('cachedigital.url_digital_account');
            } catch (\Throwable $e) {
                // fora do Laravel ou sem container
            }
        }

        $candidates[] = $_ENV['CACHE_DIGITAL_URL'] ?? null;
        $env = getenv('CACHE_DIGITAL_URL');
        $candidates[] = $env === false ? null : $env; // getenv devolve false quando não existe

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return rtrim(trim($candidate), '/');
            }
        }

        return self::DEFAULT_BASE_URL;
    }

    public static function userAgent(): string
    {
        return 'CacheDigitalCoreSDK/' . self::VERSION . ' PHP/' . PHP_VERSION;
    }
}
