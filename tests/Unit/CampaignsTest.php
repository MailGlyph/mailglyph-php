<?php

declare(strict_types=1);

namespace MailGlyph\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use MailGlyph\Exceptions\NotFoundException;
use MailGlyph\Exceptions\ValidationException;
use MailGlyph\Resources\Campaigns;
use PHPUnit\Framework\TestCase;

final class CampaignsTest extends TestCase
{
    use CreatesHttpClient;

    public function testListReturnsPaginatedCampaigns(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 'cmp_1',
                        'name' => 'Launch',
                        'subject' => 'Hello',
                        'audienceType' => 'ALL',
                        'status' => 'DRAFT',
                        'scheduledFor' => null,
                    ]
                ],
                'page' => 1,
                'pageSize' => 20,
                'total' => 1,
                'totalPages' => 1,
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        $result = $campaigns->list();

        self::assertSame('cmp_1', $result['data'][0]->id);
        self::assertSame('ALL', $result['data'][0]->audienceType);
        self::assertSame(1, $result['total']);
    }

    public function testListWithPagePageSizeAndStatusFilter(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'data' => [],
                'page' => 2,
                'pageSize' => 10,
                'total' => 0,
                'totalPages' => 0,
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        $campaigns->list(['page' => 2, 'pageSize' => 10, 'status' => 'DRAFT']);

        $query = $this->getRequestQuery($history);
        self::assertSame('2', $query['page']);
        self::assertSame('10', $query['pageSize']);
        self::assertSame('DRAFT', $query['status']);
    }

    public function testCreateWithRequiredFields(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(201, [], json_encode([
                'success' => true,
                'data' => [
                    'id' => 'cmp_1',
                    'name' => 'Launch',
                    'subject' => 'Hello',
                    'audienceType' => 'ALL',
                    'audienceCondition' => [
                        'logic' => 'AND',
                        'groups' => [
                            ['filters' => [['field' => 'subscribed', 'operator' => 'equals', 'value' => true]]],
                        ],
                    ],
                    'status' => 'DRAFT',
                    'scheduledFor' => null,
                ],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        $campaign = $campaigns->create([
            'name' => 'Launch',
            'subject' => 'Hello',
            'body' => '<h1>Hello</h1>',
            'from' => 'hello@example.com',
            'audienceType' => 'ALL',
            'audienceCondition' => [
                'logic' => 'AND',
                'groups' => [
                    ['filters' => [['field' => 'subscribed', 'operator' => 'equals', 'value' => true]]],
                ],
            ],
        ]);

        $payload = $this->getRequestJson($history);
        self::assertSame('cmp_1', $campaign->id);
        self::assertSame('ALL', $campaign->audienceType);
        self::assertSame('AND', $payload['audienceCondition']['logic']);
    }

    public function testCreateValidationError(): void
    {
        $this->expectException(ValidationException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(400, [], json_encode(['message' => 'Missing subject'], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        $campaigns->create([
            'name' => 'Launch',
            'body' => '<h1>Hello</h1>',
            'from' => 'hello@example.com',
            'audienceType' => 'ALL',
        ]);
    }

    public function testGetById(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => [
                    'id' => 'cmp_1',
                    'name' => 'Launch',
                    'subject' => 'Hello',
                    'audienceType' => 'ALL',
                    'status' => 'DRAFT',
                    'scheduledFor' => null,
                ],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        $campaign = $campaigns->get('cmp_1');

        self::assertSame('cmp_1', $campaign->id);
    }

    public function testGetByIdNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(404, [], json_encode(['message' => 'Not found'], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        $campaigns->get('missing');
    }

    public function testUpdatePartialUpdate(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => [
                    'id' => 'cmp_1',
                    'name' => 'Updated',
                    'subject' => 'Hello',
                    'audienceType' => 'ALL',
                    'status' => 'DRAFT',
                    'scheduledFor' => null,
                ],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        $campaign = $campaigns->update('cmp_1', ['name' => 'Updated']);

        $payload = $this->getRequestJson($history);
        self::assertSame('Updated', $campaign->name);
        self::assertSame('Updated', $payload['name']);
    }

    public function testSendImmediate(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [new Response(200)], $history);

        $campaigns = new Campaigns($client);
        self::assertTrue($campaigns->send('cmp_1'));
        self::assertSame('', (string) $history[0]['request']->getBody());
    }

    public function testSendScheduledWithIsoDate(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [new Response(200)], $history);

        $campaigns = new Campaigns($client);
        $campaigns->send('cmp_1', ['scheduledFor' => '2026-03-01T10:00:00Z']);

        $payload = $this->getRequestJson($history);
        self::assertSame('2026-03-01T10:00:00Z', $payload['scheduledFor']);
    }

    public function testCancelScheduledCampaignReturnsCancelledStatus(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => [
                    'id' => 'cmp_1',
                    'name' => 'Launch',
                    'subject' => 'Hello',
                    'audienceType' => 'ALL',
                    'status' => 'CANCELLED',
                    'scheduledFor' => null,
                ],
                'message' => 'Cancelled',
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        $campaign = $campaigns->cancel('cmp_1');

        self::assertSame('cmp_1', $campaign->id);
        self::assertSame('CANCELLED', $campaign->status);
    }

    public function testCancelNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(404, [], json_encode(['message' => 'Not found'], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        $campaigns->cancel('missing');
    }

    public function testTestSendsPreviewEmail(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode(['success' => true, 'message' => 'sent'], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        self::assertTrue($campaigns->test('cmp_1', 'preview@example.com'));
        $payload = $this->getRequestJson($history);
        self::assertSame('preview@example.com', $payload['email']);
    }

    public function testTestValidationError(): void
    {
        $this->expectException(ValidationException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(400, [], json_encode(['message' => 'Missing email'], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        $campaigns->test('cmp_1', '');
    }

    public function testStatsReturnsAnalyticsObject(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => ['sent' => 100, 'opened' => 50],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        $stats = $campaigns->stats('cmp_1');

        self::assertSame(100, $stats['sent']);
        self::assertSame(50, $stats['opened']);
    }

    public function testStatsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(404, [], json_encode(['message' => 'Not found'], JSON_THROW_ON_ERROR)),
        ], $history);

        $campaigns = new Campaigns($client);
        $campaigns->stats('missing');
    }
}
