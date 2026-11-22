<?php

namespace CacheDigital\Core\Tests;

use CacheDigital\Core\CacheDigital;
use CacheDigital\Core\Config;
use CacheDigital\Core\DTOs\CostumerFilterDTO;
use CacheDigital\Core\DTOs\InvoiceListQueryDTO;
use CacheDigital\Core\Exceptions\ApiException;
use CacheDigital\Core\Exceptions\CacheDigitalException;
use CacheDigital\Core\Exceptions\ConnectionException;
use CacheDigital\Core\Http\Transport;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class CacheDigitalTest extends TestCase
{
    private array $history = [];

    private function sdk(array $responses, array $options = []): CacheDigital
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));
        $http = new Client(['handler' => $stack, 'base_uri' => 'https://api.test', 'http_errors' => false]);

        return CacheDigital::make('cid', 'csecret', 'https://api.test')
            ->withTransport(new Transport('https://api.test', $http, $options + ['retry_delay_ms' => 0]));
    }

    public function test_base_url_falls_back_to_production_when_env_is_missing(): void
    {
        putenv('CACHE_DIGITAL_URL');
        unset($_ENV['CACHE_DIGITAL_URL']);
        $this->assertSame(Config::DEFAULT_BASE_URL, Config::baseUrl());
        $this->assertSame('https://sandbox.test', Config::baseUrl('https://sandbox.test/'));

        putenv('CACHE_DIGITAL_URL=https://env.test');
        $this->assertSame('https://env.test', Config::baseUrl());
        putenv('CACHE_DIGITAL_URL');
    }

    public function test_list_invoices_sends_credentials_and_query(): void
    {
        $body = $this->sdk([new Response(200, [], json_encode(['lista' => [['id' => 1]]]))])
            ->invoices()
            ->list(new InvoiceListQueryDTO(limit: 10, costumer: new CostumerFilterDTO(id: '42')));

        $this->assertSame([['id' => 1]], $body['lista']);
        $request = $this->history[0]['request'];
        $this->assertSame('/api/v2/invoice/list', $request->getUri()->getPath());
        $this->assertSame('cid', $request->getHeaderLine('client_id'));
        $this->assertSame('csecret', $request->getHeaderLine('client_secret'));
        $this->assertStringStartsWith('CacheDigitalCoreSDK/', $request->getHeaderLine('User-Agent'));
        $sent = json_decode((string) $request->getBody(), true);
        $this->assertSame('42', $sent['queryString']['costumer']['id']);
    }

    public function test_api_error_exposes_status_and_message(): void
    {
        try {
            $this->sdk([new Response(422, [], json_encode(['errors' => ['email' => ['E-mail inválido.']]]))])->invoices()->list([]);
            $this->fail('Deveria lançar ApiException.');
        } catch (ApiException $e) {
            $this->assertSame(422, $e->status());
            $this->assertTrue($e->isValidationError());
            $this->assertSame('E-mail inválido.', $e->getMessage());
            $this->assertInstanceOf(CacheDigitalException::class, $e);
        }
    }

    public function test_get_retries_on_server_error_but_post_does_not(): void
    {
        $balance = $this->sdk([new Response(503), new Response(200, [], json_encode(['open' => 10]))], ['retries' => 2])
            ->costumers()->balance('42');
        $this->assertSame(['open' => 10], $balance);
        $this->assertCount(2, $this->history);

        $this->history = [];
        $res = $this->sdk([new Response(503), new Response(201)])->storefront()->order(['cart' => []]);
        $this->assertSame(503, $res->status(), 'Compra nunca repete sozinha.');
        $this->assertCount(1, $this->history);
    }

    public function test_storefront_order_returns_validation_errors_without_throwing(): void
    {
        $res = $this->sdk([new Response(422, [], json_encode(['success' => false, 'errors' => ['cart' => ['Os preços foram atualizados.']]]))])
            ->storefront()->order(['cart' => []]);

        $this->assertFalse($res->successful());
        $this->assertSame('Os preços foram atualizados.', $res->errorMessage());
    }

    public function test_connection_error_becomes_connection_exception(): void
    {
        $this->expectException(ConnectionException::class);
        $this->sdk([new ConnectException('timeout', new Request('GET', '/'))], ['retries' => 0])->costumers()->balance('1');
    }

    public function test_charges_create_and_find(): void
    {
        $sdk = $this->sdk([
            new Response(200, [], json_encode(['id' => 'inv-1', 'pix' => ['copia_cola' => '000201...']])),
            new Response(200, [], json_encode(['id' => 'inv-1', 'status' => 'pending'])),
        ]);

        $created = $sdk->charges()->pix(['amount' => 50.0, 'reference' => 'PEDIDO-1']);
        $this->assertSame('inv-1', $created['id']);
        $this->assertSame('/api/v2/transacao/pix_dinamico', $this->history[0]['request']->getUri()->getPath());

        $found = $sdk->charges()->findByReference('PEDIDO-1');
        $this->assertSame('pending', $found['status']);
        $this->assertSame('/api/v2/transacao/reference/PEDIDO-1', $this->history[1]['request']->getUri()->getPath());
    }

    public function test_generic_request_uses_account_credentials(): void
    {
        $this->sdk([new Response(200, [], '{"ok":true}')])->request('GET', '/api/v2/qualquer');
        $this->assertSame('cid', $this->history[0]['request']->getHeaderLine('client_id'));
    }

    public function test_missing_credentials_are_reported(): void
    {
        $this->expectException(CacheDigitalException::class);
        (new \CacheDigital\Core\Services\InvoiceService('https://api.test'))->list([]);
    }
}
