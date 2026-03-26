<?php

declare(strict_types=1);

namespace MailGlyph\Models;

final class Template
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public string $subject,
        public string $body,
        public ?string $text,
        public string $from,
        public ?string $fromName,
        public ?string $replyTo,
        public string $type,
        public string $projectId,
        public string $createdAt,
        public string $updatedAt
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
            (string) ($payload['subject'] ?? ''),
            (string) ($payload['body'] ?? ''),
            isset($payload['text']) ? (string) $payload['text'] : null,
            (string) ($payload['from'] ?? ''),
            isset($payload['fromName']) ? (string) $payload['fromName'] : null,
            isset($payload['replyTo']) ? (string) $payload['replyTo'] : null,
            (string) ($payload['type'] ?? ''),
            (string) ($payload['projectId'] ?? ''),
            (string) ($payload['createdAt'] ?? ''),
            (string) ($payload['updatedAt'] ?? '')
        );
    }
}
