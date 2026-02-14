<?php

declare(strict_types=1);

namespace Mailrify\Resources;

use Mailrify\HttpClient;
use Mailrify\Models\Contact;

final class Contacts
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array{contacts: list<Contact>, cursor: ?string, hasMore: bool, total: ?int}
     */
    public function list(array $params = []): array
    {
        $response = $this->httpClient->request('GET', '/contacts', [
            'query' => $this->cleanParams($params),
        ]);

        $contactsPayload = is_array($response['contacts'] ?? null) ? $response['contacts'] : [];
        $contacts = array_map(
            static fn (mixed $item): Contact => Contact::fromArray(is_array($item) ? $item : []),
            $contactsPayload
        );

        return [
            'contacts' => array_values($contacts),
            'cursor' => isset($response['cursor']) ? (string) $response['cursor'] : null,
            'hasMore' => (bool) ($response['hasMore'] ?? false),
            'total' => isset($response['total']) ? (int) $response['total'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function create(array $params): Contact
    {
        $response = $this->httpClient->request('POST', '/contacts', [
            'json' => $params,
        ]);

        return Contact::fromArray($response);
    }

    public function get(string $id): Contact
    {
        $response = $this->httpClient->request('GET', sprintf('/contacts/%s', rawurlencode($id)));

        return Contact::fromArray($response);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function update(string $id, array $params): Contact
    {
        $response = $this->httpClient->request('PATCH', sprintf('/contacts/%s', rawurlencode($id)), [
            'json' => $params,
        ]);

        return Contact::fromArray($response);
    }

    public function delete(string $id): bool
    {
        $this->httpClient->request('DELETE', sprintf('/contacts/%s', rawurlencode($id)));

        return true;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function count(array $params = []): int
    {
        $result = $this->list(array_merge($params, ['limit' => 1]));

        if ($result['total'] !== null) {
            return $result['total'];
        }

        return count($result['contacts']);
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
