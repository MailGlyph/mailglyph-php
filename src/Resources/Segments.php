<?php

declare(strict_types=1);

namespace Mailrify\Resources;

use Mailrify\HttpClient;
use Mailrify\Models\Contact;
use Mailrify\Models\Segment;

final class Segments
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    /**
     * @return list<Segment>
     */
    public function list(): array
    {
        $response = $this->httpClient->request('GET', '/segments');

        return array_values(array_map(
            static fn (mixed $item): Segment => Segment::fromArray(is_array($item) ? $item : []),
            is_array($response) ? $response : []
        ));
    }

    /**
     * @param array<string, mixed> $params
     */
    public function create(array $params): Segment
    {
        $response = $this->httpClient->request('POST', '/segments', [
            'json' => $params,
        ]);

        return Segment::fromArray($response);
    }

    public function get(string $id): Segment
    {
        $response = $this->httpClient->request('GET', sprintf('/segments/%s', rawurlencode($id)));

        return Segment::fromArray($response);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function update(string $id, array $params): Segment
    {
        $response = $this->httpClient->request('PATCH', sprintf('/segments/%s', rawurlencode($id)), [
            'json' => $params,
        ]);

        return Segment::fromArray($response);
    }

    public function delete(string $id): bool
    {
        $this->httpClient->request('DELETE', sprintf('/segments/%s', rawurlencode($id)));

        return true;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array{contacts: list<Contact>, total: int, page: int, pageSize: int, totalPages: int}
     */
    public function listContacts(string $id, array $params = []): array
    {
        $response = $this->httpClient->request('GET', sprintf('/segments/%s/contacts', rawurlencode($id)), [
            'query' => $this->cleanParams($params),
        ]);

        $contactsPayload = is_array($response['data'] ?? null) ? $response['data'] : [];
        $contacts = array_map(
            static fn (mixed $item): Contact => Contact::fromArray(is_array($item) ? $item : []),
            $contactsPayload
        );

        return [
            'contacts' => array_values($contacts),
            'total' => (int) ($response['total'] ?? 0),
            'page' => (int) ($response['page'] ?? 1),
            'pageSize' => (int) ($response['pageSize'] ?? 0),
            'totalPages' => (int) ($response['totalPages'] ?? 0),
        ];
    }

    /**
     * @param list<string> $emails
     * @return array{added: int, notFound: list<string>}
     */
    public function addStaticMembers(string $id, array $emails): array
    {
        $response = $this->httpClient->request('POST', sprintf('/segments/%s/members', rawurlencode($id)), [
            'json' => ['emails' => array_values($emails)],
        ]);

        $notFound = $response['notFound'] ?? [];

        return [
            'added' => (int) ($response['added'] ?? 0),
            'notFound' => is_array($notFound) ? array_values(array_map('strval', $notFound)) : [],
        ];
    }

    /**
     * @param list<string> $emails
     * @return array{removed: int}
     */
    public function removeStaticMembers(string $id, array $emails): array
    {
        $response = $this->httpClient->request('DELETE', sprintf('/segments/%s/members', rawurlencode($id)), [
            'json' => ['emails' => array_values($emails)],
        ]);

        return [
            'removed' => (int) ($response['removed'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function cleanParams(array $params): array
    {
        return array_filter(
            $params,
            static fn (mixed $value): bool => $value !== null
        );
    }
}
