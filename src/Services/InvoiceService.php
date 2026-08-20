<?php

namespace CacheDigital\Core\Services;

use CacheDigital\Core\DTOs\InvoiceListQueryDTO;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Exception;

class InvoiceService
{
    protected ?string $clientId = null;
    protected ?string $clientSecret = null;
    protected string $baseUrl;
    protected Client $httpClient;

    public function __construct(?string $baseUrl = null, ?Client $httpClient = null)
    {
        $this->baseUrl = $baseUrl
            ?? $_ENV['CACHE_DIGITAL_URL']
            ?? getenv('CACHE_DIGITAL_URL')
            ?? 'https://api.cachebank.com.br';

        $this->httpClient = $httpClient ?? new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 10.0,
        ]);
    }

    public function authenticate(string $clientId, string $clientSecret): self
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        return $this;
    }

    /**
     * Aceita tanto o InvoiceListQueryDTO tipado quanto um array simples
     */
    public function list(InvoiceListQueryDTO|array $query): array
    {
        if (!$this->clientId || !$this->clientSecret) {
            throw new Exception("Credenciais (clientId e clientSecret) não foram informadas. Chame ->authenticate() antes.");
        }

        // Converte o DTO para array caso tenha sido passado como DTO
        $payload = $query instanceof InvoiceListQueryDTO ? $query->toArray() : $query;

        try {
            $response = $this->httpClient->post('/api/v2/invoice/list', [
                'headers' => [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new Exception("Erro de comunicação com a API Cache Digital: " . $e->getMessage(), $e->getCode());
        }
    }
}
