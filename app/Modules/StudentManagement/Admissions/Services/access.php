<?php

declare(strict_types=1);

require_once dirname(__DIR__, 5) . '/app/Core/dashboard.php';
require_once dirname(__DIR__, 2) . '/Students/Services/student.php';

function admissionsRedirectSignedInUser(PDO $connection): void
{
    startAppSession();
    $sessionUserId = $_SESSION['user_id'] ?? null;
    if ($sessionUserId === null) {
        return;
    }

    if ((!is_int($sessionUserId) && !is_string($sessionUserId)) || !ctype_digit((string) $sessionUserId)) {
        header('Location: login.php', true, 303);
        exit;
    }

    $accountStatement = $connection->prepare(
        "SELECT u.user_id, r.role_name
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE u.user_id = :user_id
           AND u.account_status = 'Active'
         LIMIT 1"
    );
    $accountStatement->execute(['user_id' => (int) $sessionUserId]);
    $account = $accountStatement->fetch();
    $destination = $account === false
        ? null
        : ($account['role_name'] === 'Student'
            ? studentSignInDestination($connection, (int) $account['user_id'])
            : appRoleDashboardPath($account['role_name']));

    if ($destination === null) {
        header('Location: login.php', true, 303);
        exit;
    }

    header('Location: ' . $destination, true, 303);
    exit;
}
