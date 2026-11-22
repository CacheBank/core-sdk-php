<?php

namespace CacheDigital\Core\Services;

use CacheDigital\Core\Config;
use CacheDigital\Core\DTOs\InvoiceListQueryDTO;
use CacheDigital\Core\Exceptions\CacheDigitalException;
use CacheDigital\Core\Http\Transport;
use GuzzleHttp\Client;

/**
 * Faturas da conta (API v2, credenciais client_id/client_secret da conta).
 *
 *   CacheDigital::make($id, $secret)->invoices()->list(new InvoiceListQueryDTO(...));
 *
 * Compatível com a 1.0: mesmo construtor, authenticate() e list() devolvendo o corpo da resposta.
 */
class InvoiceService
{
    protected ?string $clientId = null;
    protected ?string $clientSecret = null;
    protected string $baseUrl;
    protected Transport $transport;

    public function __construct(?string $baseUrl = null, ?Client $httpClient = null, ?Transport $transport = null)
    {
        $this->baseUrl = Config::baseUrl($baseUrl);
        $this->transport = $transport ?? new Transport($this->baseUrl, $httpClient);
    }

    public function authenticate(string $clientId, string $clientSecret): self
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        return $this;
    }

    /**
     * Lista as faturas (POST /api/v2/invoice/list). Aceita o DTO tipado ou um array.
     *
     * @throws \CacheDigital\Core\Exceptions\ApiException|\CacheDigital\Core\Exceptions\ConnectionException
     */
    public function list(InvoiceListQueryDTO|array $query): array
    {
        $payload = $query instanceof InvoiceListQueryDTO ? $query->toArray() : $query;

        // Consulta: pode tentar de novo com segurança (não cria nada).
        return $this->transport->request('POST', '/api/v2/invoice/list', [
            'headers' => $this->credentials(),
            'json' => $payload,
        ])->body();
    }

    protected function credentials(): array
    {
        if (!$this->clientId || !$this->clientSecret) {
            throw new CacheDigitalException('Credenciais (clientId e clientSecret) não foram informadas. Chame ->authenticate() antes.');
        }

        return ['client_id' => $this->clientId, 'client_secret' => $this->clientSecret];
    }
}
