<?php

namespace CacheDigital\Core\Tests;

use CacheDigital\Core\Integration\RetryPolicy;
use CacheDigital\Core\Integration\Signature;
use CacheDigital\Core\Integration\SignedClient;
use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    public function test_signature_matches_and_rejects_tampering(): void
    {
        $now = 1_800_000_000;
        $sig = Signature::sign('{"a":1}', $now, 's1');

        $this->assertStringStartsWith('v1=', $sig);
        $this->assertTrue(Signature::verify('{"a":1}', (string) $now, $sig, ['s1'], 300, $now));
        $this->assertTrue(Signature::verify('{"a":1}', (string) $now, $sig, ['novo', 's1'], 300, $now));
        $this->assertFalse(Signature::verify('{"a":2}', (string) $now, $sig, ['s1'], 300, $now));
        $this->assertFalse(Signature::verify('{"a":1}', (string) $now, $sig, ['s1'], 300, $now + 301));
        $this->assertFalse(Signature::verify('{"a":1}', null, $sig, ['s1']));
        $this->assertSame(64, strlen(Signature::generateSecret()));
    }

    public function test_signature_is_compatible_with_plain_hmac(): void
    {
        // Qualquer linguagem consegue verificar: hmac_sha256("{timestamp}.{corpo}").
        $this->assertSame('v1=' . hash_hmac('sha256', '100.corpo', 'segredo'), Signature::sign('corpo', 100, 'segredo'));
    }

    public function test_retry_policy(): void
    {
        $policy = new RetryPolicy(72);
        $this->assertSame([10, 30, 60, 300, 900, 3600, 3600], array_map(fn ($n) => $policy->delayAfterAttempt($n), range(1, 7)));

        $start = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->assertNotNull($policy->nextAttemptAt(2, $start, $start->modify('+1 hour')));
        $this->assertNull($policy->nextAttemptAt(40, $start, $start->modify('+72 hours')));
        $this->assertTrue(RetryPolicy::isRetryableStatus(null));
        $this->assertTrue(RetryPolicy::isRetryableStatus(502));
        $this->assertFalse(RetryPolicy::isRetryableStatus(422));
    }

    public function test_signed_client_posts_signed_event(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], json_encode(['success' => true, 'data' => ['result' => 'applied']]))]));
        $stack->push(Middleware::history($history));
        $client = new SignedClient('https://partner.test', 'segredo', new Client(['handler' => $stack, 'base_uri' => 'https://partner.test']));

        $res = $client->postEvent(['event_id' => 'e-1', 'event_type' => 'x', 'payload' => []]);

        $this->assertSame('applied', $res->get('data.result'));
        $request = $history[0]['request'];
        $this->assertSame('e-1', $request->getHeaderLine('X-Event-Id'));
        $this->assertTrue(Signature::verify((string) $request->getBody(), $request->getHeaderLine('X-Timestamp'), $request->getHeaderLine('X-Signature'), ['segredo']));
    }
}
