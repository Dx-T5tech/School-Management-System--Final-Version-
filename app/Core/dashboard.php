<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once __DIR__ . '/auth.php';

function requireDashboardUser(string|array $requiredRole): array
{
    startAppSession();

    $userId = $_SESSION['user_id'] ?? null;
    if ((!is_int($userId) && !is_string($userId)) || !ctype_digit((string) $userId)) {
        dashboardAccessDenied(401, 'Sign in is required to view this dashboard.');
    }

    $connection = getDatabaseConnection();
    $statement = $connection->prepare(
        'SELECT u.user_id, u.first_name, u.last_name, u.account_status, r.role_name
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE u.user_id = :user_id
         LIMIT 1'
    );
    $statement->execute(['user_id' => (int) $userId]);
    $user = $statement->fetch();

    if ($user === false || $user['account_status'] !== 'Active') {
        dashboardAccessDenied(401, 'This account is unavailable. Sign in with an active account.');
    }

    $requiredRoles = is_array($requiredRole) ? $requiredRole : [$requiredRole];
    if (!in_array($user['role_name'], $requiredRoles, true)) {
        dashboardAccessDenied(403, 'You do not have permission to view this dashboard.');
    }

    appCsrfToken();

    return $user;
}

function dashboardAccessDenied(int $statusCode, string $message): never
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($message);
}

function dashboardEscape(string|int $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
