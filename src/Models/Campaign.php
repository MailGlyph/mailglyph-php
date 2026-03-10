<?php

declare(strict_types=1);

namespace MailGlyph\Models;

final class Campaign
{
    /**
     * @param array<string, mixed>|null $audienceCondition
     * @param array<string, mixed>|null $segment
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public string $subject,
        public ?string $body,
        public ?string $from,
        public ?string $fromName,
        public ?string $replyTo,
        public string $audienceType,
        public ?array $audienceCondition,
        public ?string $segmentId,
        public string $status,
        public int $totalRecipients,
        public int $sentCount,
        public int $deliveredCount,
        public int $openedCount,
        public int $clickedCount,
        public int $bouncedCount,
        public ?string $scheduledFor,
        public ?string $sentAt,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?array $segment
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $audienceCondition = is_array($payload['audienceCondition'] ?? null) ? $payload['audienceCondition'] : null;
        $segment = is_array($payload['segment'] ?? null) ? $payload['segment'] : null;
        $scheduledFor = isset($payload['scheduledFor'])
            ? (string) $payload['scheduledFor']
            : (isset($payload['scheduledAt']) ? (string) $payload['scheduledAt'] : null);

        return new self(
            (string) ($payload['id'] ?? ''),
            (string) ($payload['name'] ?? ''),
            isset($payload['description']) ? (string) $payload['description'] : null,
            (string) ($payload['subject'] ?? ''),
            isset($payload['body']) ? (string) $payload['body'] : null,
            isset($payload['from']) ? (string) $payload['from'] : null,
            isset($payload['fromName']) ? (string) $payload['fromName'] : null,
            isset($payload['replyTo']) ? (string) $payload['replyTo'] : null,
            (string) ($payload['audienceType'] ?? $payload['type'] ?? ''),
            $audienceCondition,
            isset($payload['segmentId']) ? (string) $payload['segmentId'] : null,
            (string) ($payload['status'] ?? ''),
            (int) ($payload['totalRecipients'] ?? 0),
            (int) ($payload['sentCount'] ?? 0),
            (int) ($payload['deliveredCount'] ?? 0),
            (int) ($payload['openedCount'] ?? 0),
            (int) ($payload['clickedCount'] ?? 0),
            (int) ($payload['bouncedCount'] ?? 0),
            $scheduledFor,
            isset($payload['sentAt']) ? (string) $payload['sentAt'] : null,
            isset($payload['createdAt']) ? (string) $payload['createdAt'] : null,
            isset($payload['updatedAt']) ? (string) $payload['updatedAt'] : null,
            $segment
        );
    }
}
