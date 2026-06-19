<?php

declare(strict_types=1);

namespace MailGlyph\Models;

final class VerificationCreditSummary
{
    public function __construct(
        public int $balance,
        public bool $lowCredits
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (int) ($payload['balance'] ?? 0),
            (bool) ($payload['lowCredits'] ?? false)
        );
    }
}
