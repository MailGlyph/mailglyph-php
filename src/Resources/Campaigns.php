<?php

declare(strict_types=1);

namespace Mailrify\Resources;

use Mailrify\HttpClient;
use Mailrify\Models\Campaign;

final class Campaigns
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array{data: list<Campaign>, page: int, pageSize: int, total: int, totalPages: int}
     */
    public function list(array $params = []): array
    {
        $response = $this->httpClient->request('GET', '/campaigns', [
            'query' => $this->cleanParams($params),
        ]);

        $campaignsPayload = is_array($response['data'] ?? null) ? $response['data'] : [];
        $campaigns = array_map(
            static fn (mixed $item): Campaign => Campaign::fromArray(is_array($item) ? $item : []),
            $campaignsPayload
        );

        return [
            'data' => array_values($campaigns),
            'page' => (int) ($response['page'] ?? 1),
            'pageSize' => (int) ($response['pageSize'] ?? 0),
            'total' => (int) ($response['total'] ?? 0),
            'totalPages' => (int) ($response['totalPages'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function create(array $params): Campaign
    {
        $response = $this->httpClient->request('POST', '/campaigns', [
            'json' => $params,
        ]);

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return Campaign::fromArray($data);
    }

    public function get(string $id): Campaign
    {
        $response = $this->httpClient->request('GET', sprintf('/campaigns/%s', rawurlencode($id)));
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return Campaign::fromArray($data);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function update(string $id, array $params): Campaign
    {
        $response = $this->httpClient->request('PUT', sprintf('/campaigns/%s', rawurlencode($id)), [
            'json' => $params,
        ]);
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return Campaign::fromArray($data);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function send(string $id, array $params = []): bool
    {
        $options = [];

        if ($params !== []) {
            $options['json'] = $params;
        }

        $this->httpClient->request('POST', sprintf('/campaigns/%s/send', rawurlencode($id)), $options);

        return true;
    }

    public function cancel(string $id): Campaign
    {
        $response = $this->httpClient->request('POST', sprintf('/campaigns/%s/cancel', rawurlencode($id)));
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return Campaign::fromArray($data);
    }

    public function test(string $id, string $email): bool
    {
        $this->httpClient->request('POST', sprintf('/campaigns/%s/test', rawurlencode($id)), [
            'json' => ['email' => $email],
        ]);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(string $id): array
    {
        $response = $this->httpClient->request('GET', sprintf('/campaigns/%s/stats', rawurlencode($id)));

        return is_array($response['data'] ?? null) ? $response['data'] : [];
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
