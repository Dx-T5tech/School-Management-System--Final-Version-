<?php

declare(strict_types=1);

function admissionLocalVerificationBaseUrl(): ?string
{
    $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (
        !is_string($remoteAddress)
        || !in_array($remoteAddress, ['127.0.0.1', '::1'], true)
        || !is_string($host)
        || preg_match('/^(?:localhost|127\.0\.0\.1|\[::1\])(?::[0-9]{1,5})?$/i', $host) !== 1
    ) {
        return null;
    }

    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $scriptDirectory = str_replace(
        '\\',
        '/',
        dirname(is_string($_SERVER['SCRIPT_NAME'] ?? null) ? $_SERVER['SCRIPT_NAME'] : '/apply.php')
    );
    $scriptSegments = array_values(array_filter(
        explode('/', trim($scriptDirectory, '/')),
        static fn (string $segment): bool => $segment !== ''
    ));
    $encodedScriptPath = $scriptSegments === []
        ? ''
        : '/' . implode('/', array_map(
            static fn (string $segment): string => rawurlencode(rawurldecode($segment)),
            $scriptSegments
        ));

    return $scheme . '://' . $host . $encodedScriptPath;
}
