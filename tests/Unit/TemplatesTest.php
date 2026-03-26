<?php

declare(strict_types=1);

namespace MailGlyph\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use MailGlyph\Exceptions\NotFoundException;
use MailGlyph\Resources\Templates;
use PHPUnit\Framework\TestCase;

final class TemplatesTest extends TestCase
{
    use CreatesHttpClient;

    public function testListReturnsPaginatedTemplates(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 'tpl_1',
                        'name' => 'Welcome',
                        'description' => 'Welcome flow',
                        'subject' => 'Welcome!',
                        'body' => '<h1>Welcome</h1>',
                        'text' => 'Welcome',
                        'from' => 'hello@example.com',
                        'fromName' => 'MailGlyph',
                        'replyTo' => 'reply@example.com',
                        'type' => 'TRANSACTIONAL',
                        'projectId' => 'pr_1',
                        'createdAt' => '2026-01-01T00:00:00Z',
                        'updatedAt' => '2026-01-02T00:00:00Z',
                    ],
                ],
                'total' => 1,
                'page' => 1,
                'pageSize' => 20,
                'totalPages' => 1,
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $templates = new Templates($client);
        $result = $templates->list();

        self::assertSame('tpl_1', $result['data'][0]->id);
        self::assertSame('MailGlyph', $result['data'][0]->fromName);
        self::assertSame(1, $result['total']);
    }

    public function testListWithFilters(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'data' => [],
                'total' => 0,
                'page' => 1,
                'pageSize' => 10,
                'totalPages' => 0,
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $templates = new Templates($client);
        $templates->list(['type' => 'TRANSACTIONAL', 'search' => 'welcome', 'limit' => 10, 'cursor' => 'cur_1']);

        $query = $this->getRequestQuery($history);
        self::assertSame('TRANSACTIONAL', $query['type']);
        self::assertSame('welcome', $query['search']);
        self::assertSame('10', $query['limit']);
        self::assertSame('cur_1', $query['cursor']);
    }

    public function testCreateTemplate(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(201, [], json_encode([
                'id' => 'tpl_1',
                'name' => 'Welcome',
                'description' => null,
                'subject' => 'Welcome!',
                'body' => '<h1>Welcome</h1>',
                'text' => null,
                'from' => 'hello@example.com',
                'fromName' => null,
                'replyTo' => null,
                'type' => 'TRANSACTIONAL',
                'projectId' => 'pr_1',
                'createdAt' => '2026-01-01T00:00:00Z',
                'updatedAt' => '2026-01-01T00:00:00Z',
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $templates = new Templates($client);
        $template = $templates->create([
            'name' => 'Welcome',
            'subject' => 'Welcome!',
            'body' => '<h1>Welcome</h1>',
            'type' => 'TRANSACTIONAL',
        ]);

        self::assertSame('tpl_1', $template->id);
        self::assertNull($template->description);
        self::assertNull($template->text);
    }

    public function testGetTemplate(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'id' => 'tpl_1',
                'name' => 'Welcome',
                'description' => 'Welcome flow',
                'subject' => 'Welcome!',
                'body' => '<h1>Welcome</h1>',
                'text' => 'Welcome',
                'from' => 'hello@example.com',
                'fromName' => 'MailGlyph',
                'replyTo' => 'reply@example.com',
                'type' => 'TRANSACTIONAL',
                'projectId' => 'pr_1',
                'createdAt' => '2026-01-01T00:00:00Z',
                'updatedAt' => '2026-01-02T00:00:00Z',
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $templates = new Templates($client);
        $template = $templates->get('tpl_1');

        self::assertSame('tpl_1', $template->id);
        self::assertSame('reply@example.com', $template->replyTo);
    }

    public function testGetTemplateNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(404, [], json_encode(['message' => 'Not found'], JSON_THROW_ON_ERROR)),
        ], $history);

        $templates = new Templates($client);
        $templates->get('missing');
    }

    public function testUpdateTemplate(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'id' => 'tpl_1',
                'name' => 'Updated Welcome',
                'description' => 'Welcome flow',
                'subject' => 'Welcome!',
                'body' => '<h1>Welcome</h1>',
                'text' => 'Welcome',
                'from' => 'hello@example.com',
                'fromName' => 'MailGlyph',
                'replyTo' => 'reply@example.com',
                'type' => 'TRANSACTIONAL',
                'projectId' => 'pr_1',
                'createdAt' => '2026-01-01T00:00:00Z',
                'updatedAt' => '2026-01-03T00:00:00Z',
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $templates = new Templates($client);
        $template = $templates->update('tpl_1', ['name' => 'Updated Welcome']);

        $payload = $this->getRequestJson($history);
        self::assertSame('Updated Welcome', $template->name);
        self::assertSame('Updated Welcome', $payload['name']);
    }

    public function testDeleteTemplate(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [new Response(204)], $history);

        $templates = new Templates($client);
        self::assertTrue($templates->delete('tpl_1'));
    }
}
