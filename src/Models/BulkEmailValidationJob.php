<?php

declare(strict_types=1);

namespace MailGlyph\Models;

final class BulkEmailValidationJob
{
    public function __construct(
        public string $id,
        public string $status,
        public string $originalFilename,
        public int $fileSizeBytes,
        public int $localEmailCount,
        public int $reservedCredits,
        public ?int $confirmedEmailCount,
        public int $valid,
        public int $invalid,
        public int $unknown,
        public int $catchall,
        public int $duplicates,
        public int $spamTrap,
        public int $toxicDomains,
        public bool $readyForDownload,
        public ?string $errorCode,
        public ?string $errorMessage,
        public string $createdAt,
        public string $updatedAt,
        public ?string $completedAt,
        public ?int $creditUsed,
        public ?string $lastValidationStatus
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['id'] ?? ''),
            (string) ($payload['status'] ?? ''),
            (string) ($payload['originalFilename'] ?? ''),
            (int) ($payload['fileSizeBytes'] ?? 0),
            (int) ($payload['localEmailCount'] ?? 0),
            (int) ($payload['reservedCredits'] ?? 0),
            isset($payload['confirmedEmailCount']) ? (int) $payload['confirmedEmailCount'] : null,
            (int) ($payload['valid'] ?? 0),
            (int) ($payload['invalid'] ?? 0),
            (int) ($payload['unknown'] ?? 0),
            (int) ($payload['catchall'] ?? 0),
            (int) ($payload['duplicates'] ?? 0),
            (int) ($payload['spamTrap'] ?? 0),
            (int) ($payload['toxicDomains'] ?? 0),
            (bool) ($payload['readyForDownload'] ?? false),
            isset($payload['errorCode']) ? (string) $payload['errorCode'] : null,
            isset($payload['errorMessage']) ? (string) $payload['errorMessage'] : null,
            (string) ($payload['createdAt'] ?? ''),
            (string) ($payload['updatedAt'] ?? ''),
            isset($payload['completedAt']) ? (string) $payload['completedAt'] : null,
            isset($payload['creditUsed']) ? (int) $payload['creditUsed'] : null,
            isset($payload['lastValidationStatus']) ? (string) $payload['lastValidationStatus'] : null
        );
    }
}
