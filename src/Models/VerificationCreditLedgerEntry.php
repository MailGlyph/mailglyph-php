<?php

declare(strict_types=1);

namespace MailGlyph\Models;

final class VerificationCreditLedgerEntry
{
    public function __construct(
        public string $id,
        public int $seq,
        public string $type,
        public int $creditsDelta,
        public int $balanceAfter,
        public ?string $source,
        public ?string $status,
        public string $createdAt
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['id'] ?? ''),
            (int) ($payload['seq'] ?? 0),
            (string) ($payload['type'] ?? ''),
            (int) ($payload['creditsDelta'] ?? 0),
            (int) ($payload['balanceAfter'] ?? 0),
            isset($payload['source']) ? (string) $payload['source'] : null,
            isset($payload['status']) ? (string) $payload['status'] : null,
            (string) ($payload['createdAt'] ?? '')
        );
    }
}
