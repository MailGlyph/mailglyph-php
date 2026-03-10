<?php

declare(strict_types=1);

namespace MailGlyph\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use MailGlyph\Exceptions\NotFoundException;
use MailGlyph\Resources\Contacts;
use PHPUnit\Framework\TestCase;

final class ContactsTest extends TestCase
{
    use CreatesHttpClient;

    public function testListReturnsPaginatedContactsWithCursor(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'contacts' => [
                    [
                        'id' => 'ct_1',
                        'email' => 'a@example.com',
                        'subscribed' => true,
                        'data' => [],
                        'createdAt' => '2026-01-01T00:00:00Z',
                        'updatedAt' => '2026-01-01T00:00:00Z',
                    ],
                ],
                'cursor' => 'next_1',
                'hasMore' => true,
                'total' => 100,
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $contacts = new Contacts($client);
        $result = $contacts->list(['limit' => 1]);

        self::assertSame('ct_1', $result['contacts'][0]->id);
        self::assertSame('next_1', $result['cursor']);
        self::assertTrue($result['hasMore']);
        self::assertSame(100, $result['total']);
    }

    public function testListWithFilters(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode(['contacts' => [], 'hasMore' => false], JSON_THROW_ON_ERROR)),
        ], $history);

        $contacts = new Contacts($client);
        $contacts->list([
            'subscribed' => true,
            'search' => 'john',
            'limit' => 10,
            'cursor' => 'abc',
        ]);

        $query = $this->getRequestQuery($history);
        self::assertSame('1', $query['subscribed']);
        self::assertSame('john', $query['search']);
        self::assertSame('10', $query['limit']);
        self::assertSame('abc', $query['cursor']);
    }

    public function testGetSingleContact(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'id' => 'ct_1',
                'email' => 'a@example.com',
                'subscribed' => true,
                'data' => ['firstName' => 'A'],
                'createdAt' => '2026-01-01T00:00:00Z',
                'updatedAt' => '2026-01-01T00:00:00Z',
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $contacts = new Contacts($client);
        $contact = $contacts->get('ct_1');

        self::assertSame('a@example.com', $contact->email);
    }

    public function testGetNotFoundThrowsException(): void
    {
        $this->expectException(NotFoundException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(404, [], json_encode(['message' => 'Not found'], JSON_THROW_ON_ERROR)),
        ], $history);

        $contacts = new Contacts($client);
        $contacts->get('missing');
    }

    public function testCreateNewContactHasMetaIsNew(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(201, [], json_encode([
                'id' => 'ct_new',
                'email' => 'new@example.com',
                'subscribed' => true,
                'data' => [],
                'createdAt' => '2026-01-01T00:00:00Z',
                'updatedAt' => '2026-01-01T00:00:00Z',
                '_meta' => ['isNew' => true, 'isUpdate' => false],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $contacts = new Contacts($client);
        $contact = $contacts->create(['email' => 'new@example.com']);

        self::assertTrue((bool) ($contact->meta['isNew'] ?? false));
    }

    public function testCreateUpsertHasMetaIsUpdate(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'id' => 'ct_existing',
                'email' => 'existing@example.com',
                'subscribed' => true,
                'data' => [],
                'createdAt' => '2026-01-01T00:00:00Z',
                'updatedAt' => '2026-01-02T00:00:00Z',
                '_meta' => ['isNew' => false, 'isUpdate' => true],
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $contacts = new Contacts($client);
        $contact = $contacts->create(['email' => 'existing@example.com']);

        self::assertTrue((bool) ($contact->meta['isUpdate'] ?? false));
    }

    public function testUpdateSubscribedOnly(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'id' => 'ct_1',
                'email' => 'a@example.com',
                'subscribed' => false,
                'data' => [],
                'createdAt' => '2026-01-01T00:00:00Z',
                'updatedAt' => '2026-01-03T00:00:00Z',
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $contacts = new Contacts($client);
        $contact = $contacts->update('ct_1', ['subscribed' => false]);

        $payload = $this->getRequestJson($history);
        self::assertFalse($contact->subscribed);
        self::assertSame(false, $payload['subscribed']);
    }

    public function testUpdateCustomData(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'id' => 'ct_1',
                'email' => 'a@example.com',
                'subscribed' => true,
                'data' => ['plan' => 'premium'],
                'createdAt' => '2026-01-01T00:00:00Z',
                'updatedAt' => '2026-01-03T00:00:00Z',
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $contacts = new Contacts($client);
        $contact = $contacts->update('ct_1', ['data' => ['plan' => 'premium']]);

        self::assertSame('premium', $contact->data['plan']);
    }

    public function testDeleteSuccess(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(204),
        ], $history);

        $contacts = new Contacts($client);
        self::assertTrue($contacts->delete('ct_1'));
    }

    public function testDeleteNotFoundThrowsException(): void
    {
        $this->expectException(NotFoundException::class);

        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(404, [], json_encode(['message' => 'Not found'], JSON_THROW_ON_ERROR)),
        ], $history);

        $contacts = new Contacts($client);
        $contacts->delete('ct_missing');
    }

    public function testCountUsesTotalWhenAvailable(): void
    {
        $history = [];
        $client = $this->buildHttpClient('sk_test', [
            new Response(200, [], json_encode([
                'contacts' => [],
                'hasMore' => false,
                'total' => 42,
            ], JSON_THROW_ON_ERROR)),
        ], $history);

        $contacts = new Contacts($client);

        self::assertSame(42, $contacts->count());
    }
}
