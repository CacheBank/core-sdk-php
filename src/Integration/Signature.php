<?php

namespace CacheDigital\Core\Integration;

/**
 * Assinatura HMAC-SHA256 das chamadas entre sistemas da Cachê (Cachê ↔ Minhas Faturas e outros).
 *
 *   X-Timestamp: segundos Unix
 *   X-Signature: v1=<hex de hmac_sha256("{timestamp}.{corpo}", segredo)>
 *   X-Event-Id:  id do evento (quando houver)
 *
 * Fonte oficial do algoritmo. Os projetos que ainda têm cópia local (App\Support\Integration\IntegrationSignature)
 * devem passar a usar esta classe ao atualizar o SDK.
 */
final class Signature
{
    public const VERSION = 'v1';
    public const HEADER_SIGNATURE = 'X-Signature';
    public const HEADER_TIMESTAMP = 'X-Timestamp';
    public const HEADER_EVENT_ID = 'X-Event-Id';
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    public static function sign(string $body, int $timestamp, string $secret): string
    {
        return self::VERSION . '=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    }

    /**
     * @param string[] $secrets segredos aceitos (atual e anterior, durante a troca)
     */
    public static function verify(string $body, ?string $timestamp, ?string $signature, array $secrets, int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS, ?int $now = null): bool
    {
        if ($timestamp === null || $signature === null || !ctype_digit($timestamp)) {
            return false;
        }

        $now = $now ?? time();
        if (abs($now - (int) $timestamp) > $toleranceSeconds) {
            return false;
        }

        foreach ($secrets as $secret) {
            if (is_string($secret) && $secret !== '' && hash_equals(self::sign($body, (int) $timestamp, $secret), $signature)) {
                return true;
            }
        }

        return false;
    }

    /** Cabeçalhos prontos para uma chamada assinada. */
    public static function headers(string $body, string $secret, ?string $eventId = null, ?int $timestamp = null): array
    {
        $timestamp = $timestamp ?? time();
        $headers = [
            self::HEADER_TIMESTAMP => (string) $timestamp,
            self::HEADER_SIGNATURE => self::sign($body, $timestamp, $secret),
        ];
        if ($eventId !== null) {
            $headers[self::HEADER_EVENT_ID] = $eventId;
        }

        return $headers;
    }

    /** Gera um segredo forte (64 caracteres hex). */
    public static function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }
}
