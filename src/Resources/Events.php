<?php

declare(strict_types=1);

namespace MailGlyph\Resources;

use MailGlyph\HttpClient;
use MailGlyph\Models\TrackEventResult;

final class Events
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    /**
     * @param array<string, mixed> $params
     */
    public function track(array $params): TrackEventResult
    {
        $response = $this->httpClient->request('POST', '/v1/track', [
            'json' => $params,
        ]);

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return TrackEventResult::fromArray($data);
    }

    /**
     * @return list<string>
     */
    public function listNames(): array
    {
        $response = $this->httpClient->request('GET', '/events/names');

        return array_values(array_map(
            'strval',
            is_array($response['eventNames'] ?? null) ? $response['eventNames'] : []
        ));
    }

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return $this->listNames();
    }
}
