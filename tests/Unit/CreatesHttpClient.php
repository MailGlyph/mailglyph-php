<?php

declare(strict_types=1);

namespace MailGlyph\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use MailGlyph\HttpClient;

trait CreatesHttpClient
{
    /**
     * @param list<Response> $responses
     * @param list<array<string, mixed>> $history
     * @param array<string, mixed> $config
     */
    private function buildHttpClient(
        string $apiKey,
        array $responses,
        array &$history,
        array $config = []
    ): HttpClient {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);

        $history = [];
        $stack->push(Middleware::history($history));

        $client = new Client([
            'handler' => $stack,
            'base_uri' => $config['baseUrl'] ?? 'https://api.mailglyph.com',
            'timeout' => 30,
        ]);

        $finalConfig = array_merge($config, [
            'client' => $client,
            'sleep' => $config['sleep'] ?? static function (int $milliseconds): void {
            },
        ]);

        return new HttpClient($apiKey, $finalConfig);
    }

    /**
     * @param list<array<string, mixed>> $history
     * @return array<string, mixed>
     */
    private function getRequestJson(array $history, int $index = 0): array
    {
        $body = (string) $history[$index]['request']->getBody();

        if ($body === '') {
            return [];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param list<array<string, mixed>> $history
     * @return array<string, mixed>
     */
    private function getRequestQuery(array $history, int $index = 0): array
    {
        $query = $history[$index]['request']->getUri()->getQuery();
        parse_str($query, $params);

        $normalized = [];
        foreach ($params as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }
}
