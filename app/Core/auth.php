<?php

declare(strict_types=1);

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);

    session_start();
}

function appCsrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function appCsrfTokenIsValid(mixed $submittedToken): bool
{
    return is_string($submittedToken)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submittedToken);
}

function appPasswordMaxBytes(): int
{
    return defined('PASSWORD_ARGON2ID') ? 255 : 72;
}

function appPasswordHash(string $password): string
{
    $algorithm = defined('PASSWORD_ARGON2ID')
        ? constant('PASSWORD_ARGON2ID')
        : PASSWORD_DEFAULT;

    return password_hash($password, $algorithm);
}

function loginIdentifierHash(string $identifier): string
{
    return hash('sha256', strtolower(trim($identifier)));
}

function loginIpAddress(): string
{
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

    return filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : 'unknown';
}

function loginIsRateLimited(PDO $connection, string $identifierHash, string $ipAddress): bool
{
    $statement = $connection->prepare(
        'SELECT COUNT(*)
         FROM login_attempts
         WHERE attempted_at >= (CURRENT_TIMESTAMP - INTERVAL 15 MINUTE)
           AND (identifier_hash = :identifier_hash OR ip_address = :ip_address)'
    );
    $statement->execute([
        'identifier_hash' => $identifierHash,
        'ip_address' => $ipAddress,
    ]);

    return (int) $statement->fetchColumn() >= 8;
}

function recordFailedLogin(PDO $connection, string $identifierHash, string $ipAddress): void
{
    $statement = $connection->prepare(
        'INSERT INTO login_attempts (identifier_hash, ip_address)
         VALUES (:identifier_hash, :ip_address)'
    );
    $statement->execute([
        'identifier_hash' => $identifierHash,
        'ip_address' => $ipAddress,
    ]);
}
