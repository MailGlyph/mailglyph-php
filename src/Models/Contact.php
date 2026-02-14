<?php

declare(strict_types=1);

namespace Mailrify\Models;

final class Contact
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $id,
        public string $email,
        public bool $subscribed,
        public array $data,
        public string $createdAt,
        public string $updatedAt,
        public array $meta = []
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['id'] ?? ''),
            (string) ($payload['email'] ?? ''),
            (bool) ($payload['subscribed'] ?? false),
            is_array($payload['data'] ?? null) ? $payload['data'] : [],
            (string) ($payload['createdAt'] ?? ''),
            (string) ($payload['updatedAt'] ?? ''),
            is_array($payload['_meta'] ?? null) ? $payload['_meta'] : []
        );
    }
}
