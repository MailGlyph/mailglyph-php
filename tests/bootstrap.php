<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

foreach ([__DIR__ . '/../.env'] as $file) {
    if (!is_file($file)) {
        continue;
    }

    $values = parse_ini_file($file, false, INI_SCANNER_RAW);

    if ($values === false) {
        continue;
    }

    foreach ($values as $key => $value) {
        if (getenv((string) $key) !== false) {
            continue;
        }

        putenv(sprintf('%s=%s', $key, $value));
        $_ENV[(string) $key] = $value;
        $_SERVER[(string) $key] = $value;
    }
}
