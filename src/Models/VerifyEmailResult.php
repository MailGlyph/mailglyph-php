<?php

declare(strict_types=1);

namespace MailGlyph\Models;

final class VerifyEmailResult
{
    /**
     * @param list<string> $reasons
     */
    public function __construct(
        public string $email,
        public bool $valid,
        public string $validationMethod,
        public ?string $smtpStatus,
        public ?string $smtpDiagnosis,
        public bool $isDisposable,
        public bool $isAlias,
        public bool $isTypo,
        public bool $isPlusAddressed,
        public bool $isRandomInput,
        public bool $isPersonalEmail,
        public bool $isCatchAll,
        public bool $isGreylisted,
        public bool $domainExists,
        public bool $hasWebsite,
        public bool $hasMxRecords,
        public ?string $suggestedEmail,
        public array $reasons,
        public ?int $creditsConsumed
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['email'] ?? ''),
            (bool) ($payload['valid'] ?? false),
            (string) ($payload['validationMethod'] ?? ''),
            isset($payload['smtpStatus']) ? (string) $payload['smtpStatus'] : null,
            isset($payload['smtpDiagnosis']) ? (string) $payload['smtpDiagnosis'] : null,
            (bool) ($payload['isDisposable'] ?? false),
            (bool) ($payload['isAlias'] ?? false),
            (bool) ($payload['isTypo'] ?? false),
            (bool) ($payload['isPlusAddressed'] ?? false),
            (bool) ($payload['isRandomInput'] ?? false),
            (bool) ($payload['isPersonalEmail'] ?? false),
            (bool) ($payload['isCatchAll'] ?? false),
            (bool) ($payload['isGreylisted'] ?? false),
            (bool) ($payload['domainExists'] ?? false),
            (bool) ($payload['hasWebsite'] ?? false),
            (bool) ($payload['hasMxRecords'] ?? false),
            isset($payload['suggestedEmail']) ? (string) $payload['suggestedEmail'] : null,
            array_values(array_map('strval', is_array($payload['reasons'] ?? null) ? $payload['reasons'] : [])),
            isset($payload['creditsConsumed']) ? (int) $payload['creditsConsumed'] : null
        );
    }
}
