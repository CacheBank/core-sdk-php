<?php

namespace CacheDigital\Core\Services;

/**
 * Clientes (pagadores) da conta.
 *
 *   CacheDigital::make($id, $secret)->costumers()->balance($costumerId);
 */
class CostumerService extends InvoiceService
{
    /** Saldo do cliente: em aberto, vencido, pago (GET /api/v2/costumer/balance/{id}). */
    public function balance(string $costumerId): array
    {
        return $this->transport->request('GET', '/api/v2/costumer/balance/' . rawurlencode($costumerId), [
            'headers' => $this->credentials(),
        ])->body();
    }
}
