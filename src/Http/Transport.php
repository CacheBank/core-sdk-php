<?php

namespace CacheDigital\Core\Http;

use CacheDigital\Core\Config;
use CacheDigital\Core\Exceptions\ApiException;
use CacheDigital\Core\Exceptions\ConnectionException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

/**
 * Transporte HTTP do SDK (um lugar só para cabeçalhos, timeout, erros e nova tentativa).
 *
 * - Consultas (GET) tentam de novo em falha de rede, 5xx e 429 (`retries`, padrão 2).
 * - Escritas (POST) não tentam de novo sozinhas, para não duplicar cobrança/pedido.
 * - `request()` lança ApiException (4xx/5xx) ou ConnectionException (sem resposta).
 * - `send()` devolve a ApiResponse sem lançar em 4xx/5xx (quem chama decide).
 */
class Transport
{
    private ClientInterface $http;

    /**
     * @param array{timeout?: float, retries?: int, retry_delay_ms?: int, headers?: array} $options
     */
    public function __construct(private string $baseUrl, ?ClientInterface $http = null, private array $options = [])
    {
        $this->http = $http ?? new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => (float) ($options['timeout'] ?? Config::DEFAULT_TIMEOUT),
            'http_errors' => false,
        ]);
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function request(string $method, string $path, array $options = []): ApiResponse
    {
        $response = $this->send($method, $path, $options);
        if (!$response->successful()) {
            throw new ApiException($response);
        }

        return $response;
    }

    public function send(string $method, string $path, array $options = []): ApiResponse
    {
        $method = strtoupper($method);
        $retries = $method === 'GET' ? max(0, (int) ($this->options['retries'] ?? 2)) : 0;
        $options['headers'] = array_merge(
            ['Accept' => 'application/json', 'User-Agent' => Config::userAgent()],
            $this->options['headers'] ?? [],
            $options['headers'] ?? []
        );
        $options['http_errors'] = false;

        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                $raw = $this->http->request($method, $path, $options);
                $body = (string) $raw->getBody();
                $decoded = json_decode($body, true);
                $response = new ApiResponse($raw->getStatusCode(), is_array($decoded) ? $decoded : [], $body);

                $retryable = $response->status() >= 500 || $response->status() === 429;
                if ($retryable && $attempt <= $retries) {
                    $this->pause($attempt);
                    continue;
                }

                return $response;
            } catch (ConnectException $e) {
                if ($attempt <= $retries) {
                    $this->pause($attempt);
                    continue;
                }
                throw new ConnectionException('A API Cache Digital não respondeu: ' . $e->getMessage(), 0, $e);
            } catch (RequestException $e) {
                if ($e->hasResponse()) {
                    $raw = $e->getResponse();
                    $body = (string) $raw->getBody();
                    $decoded = json_decode($body, true);

                    return new ApiResponse($raw->getStatusCode(), is_array($decoded) ? $decoded : [], $body);
                }
                throw new ConnectionException('Erro de comunicação com a API Cache Digital: ' . $e->getMessage(), 0, $e);
            } catch (GuzzleException $e) {
                throw new ConnectionException('Erro de comunicação com a API Cache Digital: ' . $e->getMessage(), 0, $e);
            }
        }
    }

    private function pause(int $attempt): void
    {
        $delay = (int) ($this->options['retry_delay_ms'] ?? 200) * $attempt;
        if ($delay > 0) {
            usleep($delay * 1000);
        }
    }
}
