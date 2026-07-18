<?php

$envFile = BASE_PATH . '/.env';
if (!is_file($envFile)) {
    return;
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
        continue;
    }

    [$key, $value] = array_map('trim', explode('=', $line, 2));
    $value = trim($value, "\"'");
    if ($key !== '' && getenv($key) === false) {
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}
