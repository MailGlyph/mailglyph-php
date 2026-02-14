<?php

declare(strict_types=1);

namespace Mailrify\Models;

final class TrackEventResult
{
    public function __construct(
        public string $contact,
        public string $event,
        public ?string $timestamp
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['contact'] ?? ''),
            (string) ($payload['event'] ?? ''),
            isset($payload['timestamp']) ? (string) $payload['timestamp'] : null
        );
    }
}
