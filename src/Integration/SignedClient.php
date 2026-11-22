<?php

namespace CacheDigital\Core\Integration;

use CacheDigital\Core\Http\ApiResponse;
use CacheDigital\Core\Http\Transport;
use GuzzleHttp\ClientInterface;

/**
 * Cliente para chamadas internas assinadas entre sistemas (API /api/internal/v1).
 *
 *   $client = new SignedClient('https://api-allstore.cachebank.com.br', $segredo);
 *   $res = $client->postEvent(['event_id' => ..., 'event_type' => ..., 'payload' => [...]]);
 *   $res = $client->get('/api/internal/v1/accounts/150081/portal-settings');
 *
 * Não lança em 4xx/5xx: devolve a ApiResponse (quem chama decide a retentativa com RetryPolicy).
 * Sem resposta: ConnectionException.
 */
class SignedClient
{
    public const EVENTS_PATH = '/api/internal/v1/events';

    private Transport $transport;

    public function __construct(string $baseUrl, private string $secret, ?ClientInterface $http = null, array $options = [])
    {
        // Retentativa fica a cargo do outbox (RetryPolicy), não do transporte.
        $this->transport = new Transport(rtrim($baseUrl, '/'), $http, $options + ['retries' => 0]);
    }

    public function post(string $path, array $payload, ?string $eventId = null): ApiResponse
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this->transport->send('POST', $path, [
            'headers' => Signature::headers($body, $this->secret, $eventId) + ['Content-Type' => 'application/json'],
            'body' => $body,
        ]);
    }

    public function get(string $path, array $query = []): ApiResponse
    {
        return $this->transport->send('GET', $path, [
            'headers' => Signature::headers('', $this->secret),
            'query' => $query,
        ]);
    }

    /** Envia um evento do contrato v1 (exige `event_id`). */
    public function postEvent(array $event): ApiResponse
    {
        return $this->post(self::EVENTS_PATH, $event, isset($event['event_id']) ? (string) $event['event_id'] : null);
    }
}
