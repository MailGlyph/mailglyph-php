<?php

declare(strict_types=1);

namespace MailGlyph\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use MailGlyph\Exceptions\AuthenticationException;
use MailGlyph\Resources\Events;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    use CreatesHttpClient;

    public function testTrackSimpleEvent(): void
    {
        $history = [];
        $client = $this->buildHttpClient('pk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => [
                    'contact' => 'ct_1',
                    'event' => 'ev_1',
                    'timestamp' => '2026-01-01T00:00:00Z',
                ],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $events = new Events($client);
        $result = $events->track([
            'email' => 'user@example.com',
            'event' => 'signup',
        ]);

        $payload = $this->getRequestJson($history);
        self::assertSame('ct_1', $result->contact);
        self::assertSame('signup', $payload['event']);
    }

    public function testTrackWithCustomData(): void
    {
        $history = [];
        $client = $this->buildHttpClient('pk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => ['contact' => 'ct_1', 'event' => 'ev_1'],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $events = new Events($client);
        $events->track([
            'email' => 'user@example.com',
            'event' => 'purchase',
            'data' => ['amount' => 99],
        ]);

        $payload = $this->getRequestJson($history);
        self::assertSame(99, $payload['data']['amount']);
    }

    public function testTrackWorksWithPublicKey(): void
    {
        $history = [];
        $client = $this->buildHttpClient('pk_public', [
            new Response(200, [], json_encode(['success' => true, 'data' => []], JSON_THROW_ON_ERROR)),
        ], $history);

        $events = new Events($client);
        $events->track([
            'email' => 'user@example.com',
            'event' => 'open',
        ]);

        self::assertSame('/v1/track', $history[0]['request']->getUri()->getPath());
    }

    public function testTrackRejectsSecretKey(): void
    {
        $this->expectException(AuthenticationException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_secret', [
            new Response(200, [], json_encode(['success' => true], JSON_THROW_ON_ERROR)),
        ], $history);

        $events = new Events($client);
        $events->track([
            'email' => 'user@example.com',
            'event' => 'open',
        ]);
    }

    public function testListNamesReturnsArray(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_secret', [
            new Response(200, [], json_encode(['eventNames' => ['signup', 'purchase']], JSON_THROW_ON_ERROR)),
        ], $history);

        $events = new Events($client);
        $names = $events->listNames();

        self::assertSame(['signup', 'purchase'], $names);
    }

    public function testListNamesRejectsPublicKey(): void
    {
        $this->expectException(AuthenticationException::class);

        $history = [];
        $client = $this->buildHttpClient('pk_public', [
            new Response(200, [], json_encode(['eventNames' => []], JSON_THROW_ON_ERROR)),
        ], $history);

        $events = new Events($client);
        $events->listNames();
    }
}
