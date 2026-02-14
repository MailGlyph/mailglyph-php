<?php

declare(strict_types=1);

namespace Mailrify\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use Mailrify\Exceptions\NotFoundException;
use Mailrify\Resources\Segments;
use PHPUnit\Framework\TestCase;

final class SegmentsTest extends TestCase
{
    use CreatesHttpClient;

    public function testListReturnsSegments(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                [
                    'id' => 'seg_1',
                    'name' => 'Premium',
                    'description' => 'Premium users',
                    'condition' => [
                        'logic' => 'AND',
                        'groups' => [
                            ['filters' => [['field' => 'data.plan', 'operator' => 'equals', 'value' => 'premium']]],
                        ],
                    ],
                    'trackMembership' => true,
                    'memberCount' => 10,
                ],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $segments = new Segments($client);
        $result = $segments->list();

        self::assertSame('seg_1', $result[0]->id);
    }

    public function testCreateWithConditions(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(201, [], json_encode([
                'id' => 'seg_1',
                'name' => 'Premium',
                'description' => 'Premium users',
                'condition' => [
                    'logic' => 'AND',
                    'groups' => [
                        ['filters' => [['field' => 'data.plan', 'operator' => 'equals', 'value' => 'premium']]],
                    ],
                ],
                'trackMembership' => true,
                'memberCount' => 0,
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $segments = new Segments($client);
        $segment = $segments->create([
            'name' => 'Premium',
            'condition' => [
                'logic' => 'AND',
                'groups' => [
                    ['filters' => [['field' => 'data.plan', 'operator' => 'equals', 'value' => 'premium']]],
                ],
            ],
        ]);

        self::assertSame('Premium', $segment->name);
    }

    public function testGetById(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'id' => 'seg_1',
                'name' => 'Premium',
                'description' => null,
                'condition' => [
                    'logic' => 'AND',
                    'groups' => [
                        ['filters' => [['field' => 'data.plan', 'operator' => 'equals', 'value' => 'premium']]],
                    ],
                ],
                'trackMembership' => true,
                'memberCount' => 10,
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $segments = new Segments($client);
        $segment = $segments->get('seg_1');

        self::assertSame('seg_1', $segment->id);
    }

    public function testGetNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(404, [], json_encode(['message' => 'Not found'], JSON_THROW_ON_ERROR)),
        ], $history);

        $segments = new Segments($client);
        $segments->get('missing');
    }

    public function testUpdateConditions(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'id' => 'seg_1',
                'name' => 'Premium+',
                'description' => null,
                'condition' => [
                    'logic' => 'OR',
                    'groups' => [
                        ['filters' => [['field' => 'data.plan', 'operator' => 'equals', 'value' => 'premium']]],
                    ],
                ],
                'trackMembership' => false,
                'memberCount' => 5,
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $segments = new Segments($client);
        $segment = $segments->update('seg_1', [
            'condition' => [
                'logic' => 'OR',
                'groups' => [
                    ['filters' => [['field' => 'data.plan', 'operator' => 'equals', 'value' => 'premium']]],
                ],
            ],
        ]);

        self::assertSame('OR', $segment->condition['logic']);
    }

    public function testDeleteSuccess(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [new Response(204)], $history);

        $segments = new Segments($client);
        self::assertTrue($segments->delete('seg_1'));
    }

    public function testListContactsPaginated(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'data' => [[
                    'id' => 'ct_1',
                    'email' => 'a@example.com',
                    'subscribed' => true,
                    'data' => [],
                    'createdAt' => '2026-01-01T00:00:00Z',
                    'updatedAt' => '2026-01-01T00:00:00Z',
                ]],
                'total' => 1,
                'page' => 1,
                'pageSize' => 20,
                'totalPages' => 1,
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $segments = new Segments($client);
        $result = $segments->listContacts('seg_1');

        self::assertSame('ct_1', $result['contacts'][0]->id);
        self::assertSame(1, $result['total']);
    }

    public function testListContactsWithParams(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'data' => [],
                'total' => 0,
                'page' => 2,
                'pageSize' => 5,
                'totalPages' => 0,
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $segments = new Segments($client);
        $segments->listContacts('seg_1', ['page' => 2, 'pageSize' => 5]);

        $query = $this->getRequestQuery($history);
        self::assertSame('2', $query['page']);
        self::assertSame('5', $query['pageSize']);
    }
}
