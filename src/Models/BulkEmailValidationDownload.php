<?php

declare(strict_types=1);

namespace MailGlyph\Models;

final class BulkEmailValidationDownload
{
    public function __construct(
        public string $contents,
        public string $contentType,
        public ?string $filename
    ) {
    }
}
