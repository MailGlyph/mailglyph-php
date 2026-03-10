<?php

declare(strict_types=1);

namespace MailGlyph\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use MailGlyph\Exceptions\ValidationException;
use MailGlyph\Resources\Emails;
use PHPUnit\Framework\TestCase;

final class EmailsTest extends TestCase
{
    use CreatesHttpClient;

    public function testSendWithSimpleStringToAndFrom(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => [
                    'emails' => [['email' => 'em_1']],
                    'timestamp' => '2026-01-01T00:00:00Z',
                ],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $emails = new Emails($client);
        $result = $emails->send([
            'to' => 'user@example.com',
            'from' => 'hello@example.com',
            'subject' => 'Hello',
            'body' => '<p>Hi</p>',
        ]);

        $payload = $this->getRequestJson($history);
        self::assertSame('em_1', $result->emails[0]['email']);
        self::assertSame('user@example.com', $payload['to']);
        self::assertSame('hello@example.com', $payload['from']);
    }

    public function testSendWithObjectToAndFromWithNames(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode(['success' => true, 'data' => ['emails' => []]], JSON_THROW_ON_ERROR)),
        ], $history);

        $emails = new Emails($client);
        $emails->send([
            'to' => ['name' => 'Jane', 'email' => 'jane@example.com'],
            'from' => ['name' => 'Support', 'email' => 'support@example.com'],
            'subject' => 'Welcome',
            'body' => '<p>Welcome</p>',
        ]);

        $payload = $this->getRequestJson($history);
        self::assertSame('Jane', $payload['to']['name']);
        self::assertSame('Support', $payload['from']['name']);
    }

    public function testSendWithArrayRecipients(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode(['success' => true, 'data' => ['emails' => []]], JSON_THROW_ON_ERROR)),
        ], $history);

        $emails = new Emails($client);
        $emails->send([
            'to' => ['a@example.com', ['name' => 'B', 'email' => 'b@example.com']],
            'from' => 'hello@example.com',
            'subject' => 'Hello',
            'body' => '<p>Hi</p>',
        ]);

        $payload = $this->getRequestJson($history);
        self::assertCount(2, $payload['to']);
    }

    public function testSendWithTemplateAndData(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode(['success' => true, 'data' => ['emails' => []]], JSON_THROW_ON_ERROR)),
        ], $history);

        $emails = new Emails($client);
        $emails->send([
            'to' => 'user@example.com',
            'template' => 'tpl_123',
            'data' => ['firstName' => 'John'],
            'from' => 'hello@example.com',
        ]);

        $payload = $this->getRequestJson($history);
        self::assertSame('tpl_123', $payload['template']);
        self::assertSame('John', $payload['data']['firstName']);
    }

    public function testSendWithAttachments(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode(['success' => true, 'data' => ['emails' => []]], JSON_THROW_ON_ERROR)),
        ], $history);

        $emails = new Emails($client);
        $emails->send([
            'to' => 'user@example.com',
            'from' => 'hello@example.com',
            'subject' => 'Invoice',
            'body' => '<p>Attached</p>',
            'attachments' => [
                [
                    'filename' => 'invoice.pdf',
                    'content' => 'ZmFrZQ==',
                    'contentType' => 'application/pdf',
                ]
            ],
        ]);

        $payload = $this->getRequestJson($history);
        self::assertSame('invoice.pdf', $payload['attachments'][0]['filename']);
    }

    public function testSendIncludesTextWhenProvided(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode(['success' => true, 'data' => ['emails' => []]], JSON_THROW_ON_ERROR)),
        ], $history);

        $emails = new Emails($client);
        $emails->send([
            'to' => 'user@example.com',
            'from' => 'hello@example.com',
            'subject' => 'Hello',
            'body' => '<p>Hi</p>',
            'text' => 'Hi',
        ]);

        $payload = $this->getRequestJson($history);
        self::assertArrayHasKey('text', $payload);
        self::assertSame('Hi', $payload['text']);
    }

    public function testSendOmitsTextWhenNotProvided(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode(['success' => true, 'data' => ['emails' => []]], JSON_THROW_ON_ERROR)),
        ], $history);

        $emails = new Emails($client);
        $emails->send([
            'to' => 'user@example.com',
            'from' => 'hello@example.com',
            'subject' => 'Hello',
            'body' => '<p>Hi</p>',
        ]);

        $payload = $this->getRequestJson($history);
        self::assertArrayNotHasKey('text', $payload);
    }

    public function testSendKeepsEmptyTextStringForOptOut(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode(['success' => true, 'data' => ['emails' => []]], JSON_THROW_ON_ERROR)),
        ], $history);

        $emails = new Emails($client);
        $emails->send([
            'to' => 'user@example.com',
            'from' => 'hello@example.com',
            'subject' => 'Hello',
            'body' => '<p>Hi</p>',
            'text' => '',
        ]);

        $payload = $this->getRequestJson($history);
        self::assertArrayHasKey('text', $payload);
        self::assertSame('', $payload['text']);
    }

    public function testSendThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(400, [], json_encode(['message' => 'Missing to'], JSON_THROW_ON_ERROR)),
        ], $history);

        $emails = new Emails($client);
        $emails->send([
            'to' => '',
            'from' => 'hello@example.com',
            'subject' => 'Missing recipient',
            'body' => '<p>Hi</p>',
        ]);
    }

    public function testVerifyValidEmailReturnsFullResult(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => [
                    'email' => 'user@example.com',
                    'valid' => true,
                    'isDisposable' => false,
                    'isAlias' => false,
                    'isTypo' => false,
                    'isPlusAddressed' => false,
                    'isRandomInput' => false,
                    'isPersonalEmail' => true,
                    'domainExists' => true,
                    'hasWebsite' => true,
                    'hasMxRecords' => true,
                    'reasons' => ['valid'],
                ],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $emails = new Emails($client);
        $result = $emails->verify('user@example.com');

        $payload = $this->getRequestJson($history);
        self::assertTrue($result->valid);
        self::assertFalse($result->isRandomInput);
        self::assertSame('user@example.com', $payload['email']);
    }

    public function testVerifyTypoReturnsSuggestion(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'success' => true,
                'data' => [
                    'email' => 'user@gmial.com',
                    'valid' => false,
                    'isDisposable' => false,
                    'isAlias' => false,
                    'isTypo' => true,
                    'isPlusAddressed' => false,
                    'isRandomInput' => false,
                    'isPersonalEmail' => true,
                    'domainExists' => true,
                    'hasWebsite' => true,
                    'hasMxRecords' => true,
                    'suggestedEmail' => 'user@gmail.com',
                    'reasons' => ['typo'],
                ],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $emails = new Emails($client);
        $result = $emails->verify('user@gmial.com');

        self::assertTrue($result->isTypo);
        self::assertSame('user@gmail.com', $result->suggestedEmail);
    }

    public function testVerifyThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(400, [], json_encode(['message' => 'Invalid email'], JSON_THROW_ON_ERROR)),
        ], $history);

        $emails = new Emails($client);
        $emails->verify('invalid-email');
    }
}
