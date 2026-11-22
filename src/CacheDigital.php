<?php

namespace CacheDigital\Core;

use CacheDigital\Core\Http\Transport;
use CacheDigital\Core\Services\ChargeService;
use CacheDigital\Core\Services\CostumerService;
use CacheDigital\Core\Services\InvoiceService;
use CacheDigital\Core\Services\StorefrontService;

/**
 * Ponto de entrada do SDK para a API da conta (credenciais client_id/client_secret).
 *
 *   $sdk = CacheDigital::make($clientId, $clientSecret);             // endereço: Config::baseUrl()
 *   $sdk = CacheDigital::make($id, $secret, 'https://...', ['timeout' => 15, 'retries' => 2]);
 *
 *   $sdk->invoices()->list($query);
 *   $sdk->costumers()->balance($costumerId);
 *   $sdk->storefront()->order($payload);
 *
 * Integração entre sistemas (assinatura HMAC, retentativa, cliente assinado): CacheDigital\Core\Integration.
 */
class CacheDigital
{
    protected string $clientId;
    protected string $clientSecret;
    protected ?string $baseUrl;
    protected array $options;
    private ?Transport $transport = null;

    /**
     * @param array{timeout?: float, retries?: int, retry_delay_ms?: int, headers?: array} $options
     */
    public function __construct(string $clientId, string $clientSecret, ?string $baseUrl = null, array $options = [])
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->baseUrl = $baseUrl;
        $this->options = $options;
    }

    public static function make(string $clientId, string $clientSecret, ?string $baseUrl = null, array $options = []): self
    {
        return new static($clientId, $clientSecret, $baseUrl, $options);
    }

    /** Usa um transporte próprio (ex.: testes com um cliente Guzzle simulado). */
    public function withTransport(Transport $transport): self
    {
        $this->transport = $transport;

        return $this;
    }

    /** Cobranças: boleto (Bolepix), Pix, carnê, consulta, lista e status. */
    public function charges(): ChargeService
    {
        return (new ChargeService($this->baseUrl, null, $this->transport()))->authenticate($this->clientId, $this->clientSecret);
    }

    /**
     * Chamada a qualquer endpoint da API v2 ainda sem método próprio no SDK, com as credenciais da conta.
     *
     *   $sdk->request('GET', '/api/v2/...', ['query' => [...]])->body();
     */
    public function request(string $method, string $path, array $options = []): \CacheDigital\Core\Http\ApiResponse
    {
        $options['headers'] = ($options['headers'] ?? []) + ['client_id' => $this->clientId, 'client_secret' => $this->clientSecret];

        return $this->transport()->request($method, $path, $options);
    }

    public function invoices(): InvoiceService
    {
        return (new InvoiceService($this->baseUrl, null, $this->transport()))->authenticate($this->clientId, $this->clientSecret);
    }

    public function costumers(): CostumerService
    {
        return (new CostumerService($this->baseUrl, null, $this->transport()))->authenticate($this->clientId, $this->clientSecret);
    }

    public function storefront(): StorefrontService
    {
        return (new StorefrontService($this->baseUrl, null, $this->transport()))->authenticate($this->clientId, $this->clientSecret);
    }

    private function transport(): Transport
    {
        return $this->transport ??= new Transport(Config::baseUrl($this->baseUrl), null, $this->options);
    }
}
