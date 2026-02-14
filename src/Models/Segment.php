<?php

declare(strict_types=1);

namespace Mailrify\Models;

final readonly class Segment
{
    /**
     * @param array<string, mixed> $condition
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public array $condition,
        public bool $trackMembership,
        public int $memberCount
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['id'] ?? ''),
            (string) ($payload['name'] ?? ''),
            isset($payload['description']) ? (string) $payload['description'] : null,
            is_array($payload['condition'] ?? null) ? $payload['condition'] : [],
            (bool) ($payload['trackMembership'] ?? false),
            (int) ($payload['memberCount'] ?? 0)
        );
    }
}
