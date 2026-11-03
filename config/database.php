<?php

declare(strict_types=1);

function getDatabaseConnection(): PDO
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';

    if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
        throw new InvalidArgumentException('DB_PORT must be a valid TCP port.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=isms_database;charset=utf8mb4',
        $host,
        (int) $port
    );

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
