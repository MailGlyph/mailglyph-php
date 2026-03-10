<?php

declare(strict_types=1);

namespace MailGlyph\Exceptions;

use RuntimeException;
use Throwable;

class MailGlyphException extends RuntimeException
{
    /** @var array<string, mixed> */
    private array $errorData;

    /**
     * @param array<string, mixed> $errorData
     */
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        array $errorData = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
        $this->errorData = $errorData;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }
}
