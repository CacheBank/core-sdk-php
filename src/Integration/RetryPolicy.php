<?php

namespace CacheDigital\Core\Integration;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Quando tentar de novo uma entrega entre sistemas que falhou.
 *
 * 10 s → 30 s → 1 min → 5 min → 15 min → 1 h → a cada 1 h, até a janela máxima (padrão 72 h desde o início).
 * Depois disso, a entrega é dada como "falhou" (alerta e reenvio manual).
 */
final class RetryPolicy
{
    /** Espera (segundos) depois da tentativa N (1 = primeira falha). */
    public const DELAYS = [10, 30, 60, 300, 900, 3600];

    public function __construct(private int $maxHours = 72)
    {
    }

    public function delayAfterAttempt(int $attempt): int
    {
        $index = max(0, $attempt - 1);

        return self::DELAYS[min($index, count(self::DELAYS) - 1)];
    }

    /** Próxima tentativa, ou null quando a janela acabou. */
    public function nextAttemptAt(int $attempt, DateTimeInterface $windowStartedAt, ?DateTimeInterface $now = null): ?DateTimeImmutable
    {
        $now = DateTimeImmutable::createFromInterface($now ?? new DateTimeImmutable());
        $next = $now->modify('+' . $this->delayAfterAttempt($attempt) . ' seconds');
        $limit = DateTimeImmutable::createFromInterface($windowStartedAt)->modify('+' . $this->maxHours . ' hours');

        return $next > $limit ? null : $next;
    }

    /**
     * Vale tentar de novo? Sem resposta, 5xx, timeout, excesso de requisições e assinatura recusada
     * (401/403: ex. segredo trocado em um lado só) sim. Outros 4xx de contrato (ex.: 422), não.
     */
    public static function isRetryableStatus(?int $httpStatus): bool
    {
        if ($httpStatus === null) {
            return true;
        }

        return $httpStatus >= 500 || in_array($httpStatus, [401, 403, 408, 425, 429], true);
    }
}
