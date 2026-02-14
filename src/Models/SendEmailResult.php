<?php

declare(strict_types=1);

namespace Mailrify\Models;

final class SendEmailResult
{
    /**
     * @param array<int, array<string, mixed>> $emails
     */
    public function __construct(
        public array $emails,
        public ?string $timestamp
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_array($payload['emails'] ?? null) ? array_values($payload['emails']) : [],
            isset($payload['timestamp']) ? (string) $payload['timestamp'] : null
        );
    }
}
