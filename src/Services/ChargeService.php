<?php

namespace CacheDigital\Core\Services;

/**
 * Cobranças da conta (API v2 "transacao"): boleto com Pix (Bolepix), Pix dinâmico e carnê.
 *
 *   $charges = CacheDigital::make($id, $secret)->charges();
 *   $nova = $charges->pix([...]);                 // cria
 *   $charges->find($id);                          // consulta por id
 *   $charges->findByReference('PEDIDO-123');      // consulta pela sua referência
 *   $charges->list(['status' => 0]);              // lista
 *
 * Os campos de cada cobrança seguem a documentação da API v2 da Cachê. Criações e mudanças de status nunca
 * são repetidas automaticamente pelo SDK (para não duplicar cobranças): use a sua `reference` para conferir
 * com findByReference() antes de tentar de novo.
 *
 * Erros: ApiException (com status e mensagens da API) ou ConnectionException (sem resposta).
 */
class ChargeService extends InvoiceService
{
    /** Boleto com QR Code Pix na mesma cobrança (Bolepix). */
    public function boleto(array $data): array
    {
        return $this->post('/api/v2/transacao/boleto', $data);
    }

    /** Cobrança só por Pix (QR Code dinâmico). Pix é confirmado na hora. */
    public function pix(array $data): array
    {
        return $this->post('/api/v2/transacao/pix_dinamico', $data);
    }

    /** Carnê: várias parcelas de uma vez. */
    public function carne(array $data): array
    {
        return $this->post('/api/v2/transacao/carne', $data);
    }

    public function find(string $id): array
    {
        return $this->get('/api/v2/transacao/exibir/' . rawurlencode($id));
    }

    /** Busca pela referência que você enviou ao criar (ex.: número do pedido no seu sistema). */
    public function findByReference(string $reference): array
    {
        return $this->get('/api/v2/transacao/reference/' . rawurlencode($reference));
    }

    public function list(\CacheDigital\Core\DTOs\InvoiceListQueryDTO|array $query = []): array
    {
        return $this->get('/api/v2/transacao/lista', is_array($query) ? $query : $query->toArray());
    }

    /** Altera a situação da cobrança (ex.: cancelar), conforme os status aceitos pela API v2. */
    public function changeStatus(string $status, array $data): array
    {
        return $this->post('/api/v2/transacao/' . rawurlencode($status), $data);
    }

    /** Cancela todas as parcelas em aberto de um carnê. */
    public function cancelCarne(array $data): array
    {
        return $this->post('/api/v2/transacaocarne/markcancelled', $data);
    }

    /** Notificações (webhooks) enviadas para uma cobrança. */
    public function notifications(array $query): array
    {
        return $this->get('/api/v2/notificacao/transacao/exibir', $query);
    }

    private function post(string $path, array $data): array
    {
        return $this->transport->request('POST', $path, ['headers' => $this->credentials(), 'json' => $data])->body();
    }

    private function get(string $path, array $query = []): array
    {
        return $this->transport->request('GET', $path, ['headers' => $this->credentials(), 'query' => $query])->body();
    }
}
