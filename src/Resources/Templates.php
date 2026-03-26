<?php

declare(strict_types=1);

namespace MailGlyph\Resources;

use MailGlyph\HttpClient;
use MailGlyph\Models\Template;

final class Templates
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array{data: list<Template>, total: int, page: int, pageSize: int, totalPages: int}
     */
    public function list(array $params = []): array
    {
        $response = $this->httpClient->request('GET', '/templates', [
            'query' => $this->cleanParams($params),
        ]);

        $templatesPayload = is_array($response['data'] ?? null) ? $response['data'] : [];
        $templates = array_map(
            static fn(mixed $item): Template => Template::fromArray(is_array($item) ? $item : []),
            $templatesPayload
        );

        return [
            'data' => array_values($templates),
            'total' => (int) ($response['total'] ?? 0),
            'page' => (int) ($response['page'] ?? 1),
            'pageSize' => (int) ($response['pageSize'] ?? 0),
            'totalPages' => (int) ($response['totalPages'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function create(array $params): Template
    {
        $response = $this->httpClient->request('POST', '/templates', [
            'json' => $params,
        ]);

        return Template::fromArray($response);
    }

    public function get(string $id): Template
    {
        $response = $this->httpClient->request('GET', sprintf('/templates/%s', rawurlencode($id)));

        return Template::fromArray($response);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function update(string $id, array $params): Template
    {
        $response = $this->httpClient->request('PATCH', sprintf('/templates/%s', rawurlencode($id)), [
            'json' => $params,
        ]);

        return Template::fromArray($response);
    }

    public function delete(string $id): bool
    {
        $this->httpClient->request('DELETE', sprintf('/templates/%s', rawurlencode($id)));

        return true;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function cleanParams(array $params): array
    {
        return array_filter(
            $params,
            static fn(mixed $value): bool => $value !== null
        );
    }
}
