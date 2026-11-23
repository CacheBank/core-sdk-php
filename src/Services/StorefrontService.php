<?php

namespace CacheDigital\Core\Services;

use CacheDigital\Core\Http\ApiResponse;

/**
 * Vitrine (compra feita pelo portal Minhas Faturas).
 *
 *   $res = CacheDigital::make($id, $secret)->storefront()->order($payload);
 *   $res->successful() ? $res->get('data.checkout_url') : $res->errors();
 *
 * Devolve a ApiResponse sem lançar em 4xx: a compra tem erros de validação (422) que devem ir ao pagador.
 * Nunca tenta de novo sozinha (não duplica a cobrança). Sem resposta: ConnectionException.
 */
class StorefrontService extends InvoiceService
{
    /**
     * POST /api/external/v1/storefront/order (carrinho, pagador e meio de pagamento).
     * Respeita o limite de emissão de cobranças da conta: acima dele, 422 com erro em "order".
     */
    public function order(array $payload): ApiResponse
    {
        return $this->transport->send('POST', '/api/external/v1/storefront/order', [
            'headers' => $this->credentials(),
            'json' => $payload,
        ]);
    }
}
