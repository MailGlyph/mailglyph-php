<?php

declare(strict_types=1);

namespace MailGlyph\Tests\Unit;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use MailGlyph\Exceptions\ApiException;
use MailGlyph\Exceptions\AuthenticationException;
use MailGlyph\Exceptions\MailGlyphException;
use MailGlyph\Exceptions\NotFoundException;
use MailGlyph\Exceptions\RateLimitException;
use MailGlyph\Exceptions\ValidationException;
use MailGlyph\HttpClient;
use MailGlyph\Tests\Unit\Support\RecordingClient;
use MailGlyph\Tests\Unit\Support\SequenceClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class HttpClientTest extends TestCase
{
    public function testAddsBearerAuthAndUserAgentHeaders(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(200, [], json_encode(['ok' => true], JSON_THROW_ON_ERROR))]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $client = new HttpClient('sk_secret', [
            'handler' => $stack,
            'userAgent' => 'mailglyph-php/test',
        ]);

        $client->request('GET', '/contacts');

        /** @var RequestInterface $request */
        $request = $history[0]['request'];
        self::assertSame('Bearer sk_secret', $request->getHeaderLine('Authorization'));
        self::assertSame('mailglyph-php/test', $request->getHeaderLine('User-Agent'));
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function testMapsErrorsToExceptions(): void
    {
        $cases = [
            400 => ValidationException::class,
            401 => AuthenticationException::class,
            404 => NotFoundException::class,
            429 => RateLimitException::class,
            500 => ApiException::class,
        ];

        foreach ($cases as $status => $expectedException) {
            $client = new HttpClient('sk_secret', [
                'client' => new RecordingClient(
                    new Response($status, [], json_encode(['message' => 'error'], JSON_THROW_ON_ERROR))
                ),
                'maxRetries' => 0,
            ]);

            try {
                $client->request('GET', '/contacts');
                self::fail('Expected exception for status ' . $status);
            } catch (MailGlyphException $exception) {
                self::assertInstanceOf($expectedException, $exception);
                self::assertSame($status, $exception->getStatusCode());
            }
        }
    }

    public function testRetriesOnRateLimitAndRespectsRetryAfter(): void
    {
        $sleepCalls = [];
        $client = new HttpClient('sk_secret', [
            'client' => new SequenceClient([
                new Response(429, ['Retry-After' => '2'], json_encode(['message' => 'slow down'], JSON_THROW_ON_ERROR)),
                new Response(200, [], json_encode(['ok' => true], JSON_THROW_ON_ERROR)),
            ]),
            'sleep' => static function (int $milliseconds) use (&$sleepCalls): void {
                $sleepCalls[] = $milliseconds;
            },
            'maxRetries' => 3,
        ]);

        $response = $client->request('GET', '/contacts');

        self::assertSame(['ok' => true], $response);
        self::assertSame([2000], $sleepCalls);
    }

    public function testRetriesOnServerErrorsWithExponentialBackoff(): void
    {
        $sleepCalls = [];
        $client = new HttpClient('sk_secret', [
            'client' => new SequenceClient([
                new Response(500, [], json_encode(['message' => 'error'], JSON_THROW_ON_ERROR)),
                new Response(502, [], json_encode(['message' => 'error'], JSON_THROW_ON_ERROR)),
                new Response(200, [], json_encode(['ok' => true], JSON_THROW_ON_ERROR)),
            ]),
            'sleep' => static function (int $milliseconds) use (&$sleepCalls): void {
                $sleepCalls[] = $milliseconds;
            },
            'maxRetries' => 3,
        ]);

        $response = $client->request('GET', '/contacts');

        self::assertSame(['ok' => true], $response);
        self::assertCount(2, $sleepCalls);
        self::assertGreaterThanOrEqual(1000, $sleepCalls[0]);
        self::assertLessThanOrEqual(1250, $sleepCalls[0]);
        self::assertGreaterThanOrEqual(2000, $sleepCalls[1]);
        self::assertLessThanOrEqual(2250, $sleepCalls[1]);
    }

    public function testUsesCustomBaseUrl(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(200, [], json_encode(['ok' => true], JSON_THROW_ON_ERROR))]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $client = new HttpClient('sk_secret', [
            'handler' => $stack,
            'baseUrl' => 'https://staging.mailglyph.test',
        ]);

        $client->request('GET', '/contacts');

        /** @var RequestInterface $request */
        $request = $history[0]['request'];
        self::assertSame('staging.mailglyph.test', $request->getUri()->getHost());
    }

    public function testAppliesTimeoutOption(): void
    {
        $recordingClient = new RecordingClient(new Response(200, [], json_encode(['ok' => true], JSON_THROW_ON_ERROR)));
        $client = new HttpClient('sk_secret', [
            'client' => $recordingClient,
            'timeout' => 1234,
        ]);

        $client->request('GET', '/contacts');

        self::assertSame(1.234, $recordingClient->lastOptions['timeout']);
    }
}
